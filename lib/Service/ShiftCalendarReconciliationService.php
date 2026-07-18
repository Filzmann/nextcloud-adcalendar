<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Stellt den vollständigen führenden AD-Dienstbestand aller standardmäßig aktiven persönlichen Kalender periodisch wieder her.
 * Zukunftsvertrag: Bei bidirektionalem Ausbau bleibt dies der ausgehende Konsistenzschritt nach Import und Konfliktauflösung.
 */
final class ShiftCalendarReconciliationService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private CalendarPreferenceService $preferences,
        private ShiftCalendarPublisher $publisher,
        private LoggerInterface $logger,
    ) {}

    /** @return array{attempted: int, succeeded: int, failed: int} */
    public function reconcileAll(): array {
        $result = ['attempted' => 0, 'succeeded' => 0, 'failed' => 0];
        $employeeUids = array_values(array_unique(array_merge(
            $this->entries->findEmployeeUidsWithShifts(),
            $this->preferences->shiftCalendarSyncEmployeeUids(),
        )));
        sort($employeeUids, SORT_STRING);
        foreach ($employeeUids as $employeeUid) {
            if (!$this->preferences->shiftCalendarSyncEnabled($employeeUid)) continue;
            $result['attempted']++;
            if ($this->reconcileEmployee($employeeUid)) $result['succeeded']++;
            else $result['failed']++;
        }
        return $result;
    }

    public function reconcileEmployee(string $employeeUid): bool {
        if (!$this->preferences->shiftCalendarSyncEnabled($employeeUid)) return false;
        try {
            $this->publisher->replaceAll($employeeUid, $this->entries->findShiftsForEmployee($employeeUid));
            return true;
        } catch (\Throwable $error) {
            $this->logger->error('Periodischer Dienstkalender-Abgleich ist für ein aktives Konto fehlgeschlagen.', ['exception' => $error]);
            return false;
        }
    }
}
