<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;

/** Zweck: Orchestriert Wochenansicht, Summen und die Sperrtermin-Ableitung. */
final class CalendarService {
    public function __construct(private CalendarEntryRepository $entries) {}

    public function week(DateTimeImmutable $start, array $employees): array {
        $end = $start->modify('+7 days');
        $entries = $this->entries->findRange($start, $end, array_column($employees, 'uid'));
        $shifts = array_values(array_filter($entries, static fn(CalendarEntry $entry): bool => $entry->type() === CalendarEntry::TYPE_SHIFT));
        $serialized = [];
        foreach ($entries as $entry) {
            $row = $entry->toArray();
            $row['isBlocked'] = $entry->type() === CalendarEntry::TYPE_APPOINTMENT && $entry->parentEntryId() === null;
            $serialized[] = $row;
        }
        $summaries = [];
        foreach ($employees as $employee) {
            $own = array_filter($shifts, static fn(CalendarEntry $entry): bool => $entry->employeeUid() === $employee['uid']);
            $summaries[$employee['uid']] = ['shiftCount' => count($own), 'shiftMinutes' => array_sum(array_map(static fn(CalendarEntry $entry): int => $entry->durationMinutes(), $own))];
        }
        return ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d'), 'employees' => $employees, 'entries' => $serialized, 'summaries' => $summaries];
    }

    public function save(array $payload, ?int $id, string $actorUid): int {
        if ($id !== null) $payload['id'] = $id;
        $entry = CalendarEntry::get($payload);
        if ($entry->type() === CalendarEntry::TYPE_APPOINTMENT) {
            $parents = $this->entries->containingShifts($entry->employeeUid(), $entry->start(), $entry->end(), $entry->id());
            if (count($parents) > 1) throw new InvalidArgumentException('Der Termin liegt in mehreren Diensten. Bitte Dienste zuerst korrigieren.');
            $data = $entry->toArray();
            $data['parentEntryId'] = $parents[0]->id() ?? null;
            $entry = CalendarEntry::get($data);
        }
        $savedId = $this->entries->save($entry, $actorUid);
        if ($entry->type() === CalendarEntry::TYPE_SHIFT && $entry->id() !== null) {
            foreach ($this->entries->children($savedId) as $child) {
                if (!$child->isWithin($entry)) $this->entries->detachChild((int)$child->id());
            }
        }
        return $savedId;
    }

    public function existing(int $id): CalendarEntry {
        return $this->entries->find($id) ?? throw new InvalidArgumentException('Kalendereintrag nicht gefunden.');
    }

    public function deletionPreview(int $id): array {
        $entry = $this->existing($id);
        $children = $entry->type() === CalendarEntry::TYPE_SHIFT ? $this->entries->children($id) : [];
        return ['entry' => $entry->toArray(), 'children' => array_map(static fn(CalendarEntry $child): array => $child->toArray(), $children)];
    }

    public function delete(int $id, string $childMode): void {
        $entry = $this->existing($id);
        if ($entry->type() !== CalendarEntry::TYPE_SHIFT) { $this->entries->delete($id); return; }
        if ($this->entries->children($id) === []) { $this->entries->delete($id); return; }
        if (!in_array($childMode, ['delete', 'detach'], true)) throw new InvalidArgumentException('Bitte Behandlung der enthaltenen Termine bestaetigen.');
        $this->entries->deleteShift($id, $childMode);
    }
}
