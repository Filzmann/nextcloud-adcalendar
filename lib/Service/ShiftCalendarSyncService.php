<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use InvalidArgumentException;
use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use Psr\Log\LoggerInterface;

/** Zweck: Orchestriert persönliches Opt-in und fehlertolerante, ausschließlich ausgehende Dienstveröffentlichung. */
final class ShiftCalendarSyncService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private CalendarPreferenceService $preferences,
        private ShiftCalendarPublisher $publisher,
        private LoggerInterface $logger,
    ) {}

    public function status(string $employeeUid): array {
        return [
            'enabled' => trim($employeeUid) !== '' && $this->preferences->shiftCalendarSyncEnabled($employeeUid),
            'calendarName' => ShiftCalendarPublisher::CALENDAR_NAME,
        ];
    }

    public function configure(string $employeeUid, bool $enabled): array {
        if (trim($employeeUid) === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
        if ($enabled) {
            $this->publisher->replaceAll($employeeUid, $this->entries->findShiftsForEmployee($employeeUid));
            $this->preferences->saveShiftCalendarSyncEnabled($employeeUid, true);
        } else {
            $this->publisher->removeCalendar($employeeUid);
            $this->preferences->saveShiftCalendarSyncEnabled($employeeUid, false);
        }
        return $this->status($employeeUid);
    }

    public function publish(CalendarEntry $entry): bool {
        if (!$this->eligible($entry)) return false;
        try {
            $this->publisher->publish($entry);
            return true;
        } catch (\Throwable $error) {
            $this->logFailure('Dienst konnte nicht in den privaten Nextcloud-Kalender übertragen werden.', $entry, $error);
            return false;
        }
    }

    public function remove(CalendarEntry $entry): bool {
        if (!$this->eligible($entry)) return false;
        try {
            $this->publisher->remove($entry->employeeUid(), (int)$entry->id());
            return true;
        } catch (\Throwable $error) {
            $this->logFailure('Dienst konnte nicht aus dem privaten Nextcloud-Kalender entfernt werden.', $entry, $error);
            return false;
        }
    }

    private function eligible(CalendarEntry $entry): bool {
        return $entry->type() === CalendarEntry::TYPE_SHIFT
            && $entry->id() !== null
            && $this->preferences->shiftCalendarSyncEnabled($entry->employeeUid());
    }

    private function logFailure(string $message, CalendarEntry $entry, \Throwable $error): void {
        $this->logger->error($message, ['entryId' => $entry->id(), 'exception' => $error]);
    }
}
