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
        public array $uids = [];
        public function findShiftsForEmployee(string $uid): array { return $this->shifts[$uid] ?? []; }
        public function findEmployeeUidsWithShifts(): array { return $this->uids; }
    }
}

namespace OCA\AdCalendar\Service {
    final class CalendarPreferenceService {
        public array $uids = [];
        public array $disabled = [];
        public function shiftCalendarSyncEmployeeUids(): array { return $this->uids; }
        public function shiftCalendarSyncEnabled(string $uid): bool { return !in_array($uid, $this->disabled, true); }
    }
}
namespace OCA\AdCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore {
        public array $uids = [];
        public function connectedEmployeeUids(): array { return $this->uids; }
        public function hasConnections(string $uid): bool { return in_array($uid, $this->uids, true); }
    }
    final class ExternalShiftCalendarPublisher {
        public array $replaced = [];
        public function replaceAll(string $uid, array $shifts): void { $this->replaced[] = [$uid, $shifts]; }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/Service/ShiftCalendarReconciliationService.php';

    use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\CalendarSync\ExternalShiftCalendarPublisher;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\CalendarPreferenceService;
    use OCA\AdCalendar\Service\ShiftCalendarReconciliationService;
    use Psr\Log\LoggerInterface;

    $preferences = new CalendarPreferenceService();
    $preferences->uids = ['person-c'];
    $preferences->disabled = ['person-disabled'];
    $entries = new CalendarEntryRepository();
    $entries->uids = ['person-disabled', 'person-b', 'person-a'];
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

    $external = new ExternalShiftCalendarPublisher();
    $externalConnections = new ExternalCalendarConnectionStore();
    $service = new ShiftCalendarReconciliationService($entries, $preferences, $publisher, $external, $externalConnections, $logger);
    $result = $service->reconcileAll();
    if ($result !== ['attempted' => 3, 'succeeded' => 2, 'failed' => 1]) throw new RuntimeException('Abgleichszähler bilden Erfolg und Fehler nicht korrekt ab.');
    if (array_column($publisher->replaced, 0) !== ['person-a', 'person-b', 'person-c']) throw new RuntimeException('Ein DAV-Fehler verhindert den Abgleich nachfolgender aktiver Konten.');
    if (count($publisher->replaced[0][1] ?? []) !== 1 || ($publisher->replaced[2][1] ?? null) !== []) throw new RuntimeException('Abgleich verwendet nicht den vollständigen führenden Dienstbestand je Person.');
    if (count($logger->errors) !== 1 || str_contains(json_encode($logger->errors), 'person-b')) throw new RuntimeException('Abgleichsfehler wird nicht sicher und datensparsam protokolliert.');
    if ($service->reconcileEmployee('person-disabled') || count($publisher->replaced) !== 3) throw new RuntimeException('Gezielter Abgleich ignoriert den persönlichen Opt-out nicht.');
    if (!$service->reconcileEmployee('person-default') || count($publisher->replaced) !== 4) throw new RuntimeException('Gezielter Abgleich verwendet den standardmäßig aktiven Dienstkalender nicht.');

    echo "ShiftCalendarReconciliationServiceTest: OK\n";
}
