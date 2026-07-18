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
        public array $enabled = [];
        public function shiftCalendarSyncEnabled(string $uid): bool { return $this->enabled[$uid] ?? false; }
        public function saveShiftCalendarSyncEnabled(string $uid, bool $enabled): bool { return $this->enabled[$uid] = $enabled; }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/Service/ShiftCalendarSyncService.php';

    use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\CalendarPreferenceService;
    use OCA\AdCalendar\Service\ShiftCalendarSyncService;
    use Psr\Log\LoggerInterface;

    $shift = CalendarEntry::get(['id' => 9, 'employeeUid' => 'sync-person', 'start' => '2026-07-20T08:00:00+02:00', 'end' => '2026-07-20T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '']);
    $appointment = CalendarEntry::get(['id' => 10, 'employeeUid' => 'sync-person', 'start' => '2026-07-20T10:00:00+02:00', 'end' => '2026-07-20T11:00:00+02:00', 'type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => 'Termin']);
    $publisher = new class implements ShiftCalendarPublisher {
        public array $replaced = [];
        public array $published = [];
        public array $removed = [];
        public array $removedCalendars = [];
        public bool $fail = false;
        public function replaceAll(string $employeeUid, array $shifts): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->replaced[] = [$employeeUid, $shifts]; }
        public function publish(CalendarEntry $shift): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->published[] = $shift; }
        public function remove(string $employeeUid, int $shiftId): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->removed[] = [$employeeUid, $shiftId]; }
        public function removeCalendar(string $employeeUid): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->removedCalendars[] = $employeeUid; }
    };
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; }
    };
    $repository = new CalendarEntryRepository();
    $repository->shifts['sync-person'] = [$shift];
    $preferences = new CalendarPreferenceService();
    $service = new ShiftCalendarSyncService($repository, $preferences, $publisher, $logger);

    if ($service->status('sync-person') !== ['enabled' => false, 'calendarName' => 'AD Dienste']) throw new RuntimeException('Sicherer Opt-in-Standard fehlt.');
    $enabled = $service->configure('sync-person', true);
    if (!$enabled['enabled'] || count($publisher->replaced) !== 1 || !$preferences->enabled['sync-person']) throw new RuntimeException('Opt-in synchronisiert vorhandene Dienste nicht atomar vor dem Aktivieren.');
    if (!$service->publish($shift) || count($publisher->published) !== 1) throw new RuntimeException('Aktiver Dienst wurde nicht veröffentlicht.');
    if ($service->publish($appointment) || count($publisher->published) !== 1) throw new RuntimeException('Termin wurde unzulässig als Dienst veröffentlicht.');
    if (!$service->remove($shift) || $publisher->removed !== [['sync-person', 9]]) throw new RuntimeException('Aktiver Dienst wurde nicht aus dem Zielkalender entfernt.');

    $publisher->fail = true;
    if ($service->publish($shift) || $logger->errors === []) throw new RuntimeException('DAV-Fehler gefährdet die führenden AD-Daten oder wird nicht protokolliert.');
    $publisher->fail = false;
    $disabled = $service->configure('sync-person', false);
    if ($disabled['enabled'] || $publisher->removedCalendars !== ['sync-person'] || $preferences->enabled['sync-person']) throw new RuntimeException('Opt-out entfernt den app-eigenen Kalender nicht vor dem Deaktivieren.');

    $publisher->fail = true;
    try {
        $service->configure('failed-person', true);
        throw new RuntimeException('Fehlgeschlagener Opt-in wurde gespeichert.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fehlgeschlagener Opt-in wurde gespeichert.') throw $error;
    }
    if ($preferences->shiftCalendarSyncEnabled('failed-person')) throw new RuntimeException('Fehlgeschlagener Opt-in bleibt aktiv.');

    echo "ShiftCalendarSyncServiceTest: OK\n";
}
