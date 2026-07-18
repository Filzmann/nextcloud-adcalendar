<?php

declare(strict_types=1);

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; }
    }
}

namespace OCA\AdCalendar\Repository {
    final class CalendarEntryRepository {
        public array $shifts = [];
        public function findShiftsForEmployee(string $uid): array { return $this->shifts[$uid] ?? []; }
    }
}

namespace OCA\AdCalendar\Service {
    final class CalendarPreferenceService {
        public array $uids = [];
        public function shiftCalendarSyncEmployeeUids(): array { return $this->uids; }
        public function shiftCalendarSyncEnabled(string $uid): bool { return in_array($uid, $this->uids, true); }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/Service/ShiftCalendarReconciliationService.php';

    use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\CalendarPreferenceService;
    use OCA\AdCalendar\Service\ShiftCalendarReconciliationService;
    use Psr\Log\LoggerInterface;

    $preferences = new CalendarPreferenceService();
    $preferences->uids = ['person-a', 'person-b', 'person-c'];
    $entries = new CalendarEntryRepository();
    $entries->shifts['person-a'] = [CalendarEntry::get([
        'id' => 1,
        'employeeUid' => 'person-a',
        'start' => '2026-07-20T08:00:00+02:00',
        'end' => '2026-07-20T16:00:00+02:00',
        'type' => CalendarEntry::TYPE_SHIFT,
        'title' => '',
    ])];
    $publisher = new class implements ShiftCalendarPublisher {
        public array $replaced = [];
        public function replaceAll(string $employeeUid, array $shifts): void {
            $this->replaced[] = [$employeeUid, $shifts];
            if ($employeeUid === 'person-b') throw new RuntimeException('DAV vorübergehend nicht erreichbar');
        }
        public function publish(CalendarEntry $shift): void {}
        public function remove(string $employeeUid, int $shiftId): void {}
        public function removeCalendar(string $employeeUid): void {}
    };
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; }
    };

    $service = new ShiftCalendarReconciliationService($entries, $preferences, $publisher, $logger);
    $result = $service->reconcileAll();
    if ($result !== ['attempted' => 3, 'succeeded' => 2, 'failed' => 1]) throw new RuntimeException('Abgleichszähler bilden Erfolg und Fehler nicht korrekt ab.');
    if (array_column($publisher->replaced, 0) !== ['person-a', 'person-b', 'person-c']) throw new RuntimeException('Ein DAV-Fehler verhindert den Abgleich nachfolgender Opt-ins.');
    if (count($publisher->replaced[0][1] ?? []) !== 1 || ($publisher->replaced[2][1] ?? null) !== []) throw new RuntimeException('Abgleich verwendet nicht den vollständigen führenden Dienstbestand je Person.');
    if (count($logger->errors) !== 1 || str_contains(json_encode($logger->errors), 'person-b')) throw new RuntimeException('Abgleichsfehler wird nicht sicher und datensparsam protokolliert.');
    if ($service->reconcileEmployee('person-disabled') || count($publisher->replaced) !== 3) throw new RuntimeException('Gezielter Abgleich veröffentlicht ohne persönliches Opt-in.');

    echo "ShiftCalendarReconciliationServiceTest: OK\n";
}
