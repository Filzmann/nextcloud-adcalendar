<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;

/**
 * Zweck: Orchestriert Wochenansicht, Persistenz, Dienst-Termin-Zuordnung und gemeinsame Meetingluecken.
 * Zusammenspiel: ApiController -> CalendarService -> DefaultShiftMaterializer/CalendarEntryRepository/MeetingAvailabilityService.
 */
final class CalendarService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private MeetingAvailabilityService $meetingAvailability,
        private DefaultShiftMaterializer $defaultShifts,
        private AbsenceService $absences,
    ) {}

    public function week(DateTimeImmutable $start, array $employees): array {
        $end = $start->modify('+7 days');
        $absences = $this->absences->query($start, $end, array_column($employees, 'uid'));
        $this->defaultShifts->syncWeek($start, array_column($employees, 'uid'), $absences);
        $entries = $this->entries->findRange($start, $end, array_column($employees, 'uid'));
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'employees' => $employees,
            'entries' => array_map([$this, 'serializeEntry'], $entries),
            'absences' => array_map(static fn($absence): array => $absence->toArray(), $absences),
        ];
    }

    public function save(array $payload, ?int $id, string $actorUid): int {
        if ($id !== null) {
            $existing = $this->existing($id);
            if ($existing->defaultDate() !== null) {
                if ($existing->employeeUid() !== (string)($payload['employeeUid'] ?? '')) {
                    throw new InvalidArgumentException('Ein Standarddienst kann nicht einer anderen Person zugeordnet werden.');
                }
                $payload = array_replace($payload, ['defaultDate' => $existing->defaultDate(), 'defaultModified' => true, 'defaultDeleted' => false]);
            }
            $payload['id'] = $id;
        }
        $entry = CalendarEntry::get($payload);
        $this->absences->assertWritable($entry->employeeUid(), $entry->start(), $entry->end());
        $this->assertTypeUnchanged($entry, $id);
        $this->assertShiftDoesNotOverlap($entry);
        $entry = $this->assignContainingShift($entry);
        $savedId = $this->entries->save($entry, $actorUid);
        $this->detachChildrenOutsideShift($entry, $savedId);
        return $savedId;
    }

    public function existing(int $id): CalendarEntry {
        return $this->entries->find($id) ?? throw new InvalidArgumentException('Kalendereintrag nicht gefunden.');
    }

    public function meetingGaps(DateTimeImmutable $start, array $employeeUids, int $durationMinutes): array {
        $end = $start->modify('+7 days');
        $absences = $this->absences->query($start, $end, $employeeUids);
        $this->defaultShifts->syncWeek($start, $employeeUids, $absences);
        return $this->meetingAvailability->find($this->entries->findRange($start, $end, $employeeUids), $employeeUids, $start, $end, $durationMinutes, $absences);
    }

    public function deletionPreview(int $id): array {
        $entry = $this->existing($id);
        $children = $entry->type() === CalendarEntry::TYPE_SHIFT ? $this->entries->children($id) : [];
        return ['entry' => $entry->toArray(), 'children' => array_map(static fn(CalendarEntry $child): array => $child->toArray(), $children)];
    }

    public function delete(int $id, string $childMode): void {
        $entry = $this->existing($id);
        $children = $entry->type() === CalendarEntry::TYPE_SHIFT ? $this->entries->children($id) : [];
        if ($entry->type() !== CalendarEntry::TYPE_SHIFT) {
            $this->entries->delete($id);
            return;
        }
        if ($children !== [] && !in_array($childMode, ['delete', 'detach'], true)) {
            throw new InvalidArgumentException('Bitte Behandlung der enthaltenen Termine bestätigen.');
        }
        $childMode = $children === [] ? 'detach' : $childMode;
        if ($entry->defaultDate() !== null) {
            $this->entries->deleteDefaultShift($id, $childMode);
            return;
        }
        $this->entries->deleteShift($id, $childMode);
    }

    private function serializeEntry(CalendarEntry $entry): array {
        return $entry->toArray() + [
            'isBlocked' => $entry->type() === CalendarEntry::TYPE_APPOINTMENT && $entry->parentEntryId() === null,
        ];
    }

    private function assertTypeUnchanged(CalendarEntry $entry, ?int $id): void {
        if ($id !== null && $this->existing($id)->type() !== $entry->type()) {
            throw new InvalidArgumentException('Der Typ eines bestehenden Eintrags kann nicht geändert werden.');
        }
    }

    private function assertShiftDoesNotOverlap(CalendarEntry $entry): void {
        if ($entry->type() !== CalendarEntry::TYPE_SHIFT) return;
        if ($this->entries->overlappingShifts($entry->employeeUid(), $entry->start(), $entry->end(), $entry->id()) !== []) {
            throw new InvalidArgumentException('Der Dienst überschneidet sich mit einem bestehenden Dienst dieser Person.');
        }
    }

    private function assignContainingShift(CalendarEntry $entry): CalendarEntry {
        if ($entry->type() !== CalendarEntry::TYPE_APPOINTMENT) return $entry;
        $parents = $this->entries->containingShifts($entry->employeeUid(), $entry->start(), $entry->end(), $entry->id());
        if (count($parents) > 1) {
            throw new InvalidArgumentException('Der Termin liegt in mehreren Diensten. Bitte Dienste zuerst korrigieren.');
        }
        return CalendarEntry::get(array_replace($entry->toArray(), ['parentEntryId' => $parents[0]->id() ?? null]));
    }

    private function detachChildrenOutsideShift(CalendarEntry $entry, int $savedId): void {
        if ($entry->type() !== CalendarEntry::TYPE_SHIFT || $entry->id() === null) return;
        foreach ($this->entries->children($savedId) as $child) {
            if (!$child->isWithin($entry)) $this->entries->detachChild((int)$child->id());
        }
    }
}
