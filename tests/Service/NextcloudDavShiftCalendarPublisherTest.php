<?php

declare(strict_types=1);

namespace OCA\DAV\CalDAV {
    final class CalDavBackend {
        public const CALENDAR_TYPE_CALENDAR = 0;
        public array $calendars = [];
        public array $objects = [];
        public array $createdObjects = [];
        public array $updatedObjects = [];
        public array $deletedObjects = [];
        public array $deletedCalendars = [];
        private int $nextId = 1;

        public function getCalendarsForUser(string $principal): array {
            return array_values(array_filter($this->calendars, static fn(array $calendar): bool => $calendar['principaluri'] === $principal));
        }
        public function createCalendar(string $principal, string $uri, array $properties): int {
            $id = $this->nextId++;
            $this->calendars[$id] = ['id' => $id, 'uri' => $uri, 'principaluri' => $principal, '{DAV:}displayname' => $properties['{DAV:}displayname'] ?? $uri];
            $this->objects[$id] = [];
            return $id;
        }
        public function getCalendarObjects(int $calendarId): array { return array_values($this->objects[$calendarId] ?? []); }
        public function getCalendarObject(int $calendarId, string $uri): ?array { return $this->objects[$calendarId][$uri] ?? null; }
        public function createCalendarObject(int $calendarId, string $uri, string $data): void {
            $this->createdObjects[] = [$calendarId, $uri];
            $this->objects[$calendarId][$uri] = ['uri' => $uri, 'calendardata' => $data];
        }
        public function updateCalendarObject(int $calendarId, string $uri, string $data): void {
            $this->updatedObjects[] = [$calendarId, $uri];
            $this->objects[$calendarId][$uri] = ['uri' => $uri, 'calendardata' => $data];
        }
        public function deleteCalendarObject(int $calendarId, string $uri, int $type = 0, bool $force = false): void {
            $this->deletedObjects[] = [$calendarId, $uri, $force];
            unset($this->objects[$calendarId][$uri]);
        }
        public function deleteCalendar(int $calendarId, bool $force = false): void {
            $this->deletedCalendars[] = [$calendarId, $force];
            unset($this->calendars[$calendarId], $this->objects[$calendarId]);
        }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarEventSerializer.php';
    require_once __DIR__ . '/../../lib/CalendarSync/NextcloudDavShiftCalendarPublisher.php';

    use OCA\AdCalendar\CalendarSync\NextcloudDavShiftCalendarPublisher;
    use OCA\AdCalendar\CalendarSync\ShiftCalendarEventSerializer;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\DAV\CalDAV\CalDavBackend;

    $shift = static fn(int $id, string $title = ''): CalendarEntry => CalendarEntry::get([
        'id' => $id,
        'employeeUid' => 'sync-person',
        'start' => '2026-07-20T08:00:00+02:00',
        'end' => '2026-07-20T16:00:00+02:00',
        'type' => CalendarEntry::TYPE_SHIFT,
        'title' => $title,
    ]);

    $backend = new CalDavBackend();
    $publisher = new NextcloudDavShiftCalendarPublisher($backend, new ShiftCalendarEventSerializer());
    $publisher->replaceAll('sync-person', [$shift(7), $shift(8)]);
    if (count($backend->calendars) !== 1 || count($backend->createdObjects) !== 2) throw new RuntimeException('Opt-in legt Kalender und vorhandene Dienste nicht an.');
    $calendarId = (int)array_key_first($backend->calendars);
    if (($backend->calendars[$calendarId]['{DAV:}displayname'] ?? '') !== 'AD Dienste') throw new RuntimeException('Dedizierter Kalendername fehlt.');

    $publisher->publish($shift(7, 'Geändert'));
    if (($backend->updatedObjects[0][1] ?? '') !== 'adcalendar-shift-7.ics') throw new RuntimeException('Bestehender Dienst wurde nicht idempotent aktualisiert.');

    $backend->objects[$calendarId]['privat.ics'] = ['uri' => 'privat.ics', 'calendardata' => 'privat'];
    $backend->objects[$calendarId]['adcalendar-shift-99.ics'] = ['uri' => 'adcalendar-shift-99.ics', 'calendardata' => 'fremd'];
    $publisher->replaceAll('sync-person', [$shift(7)]);
    if (isset($backend->objects[$calendarId]['adcalendar-shift-8.ics'])
        || !isset($backend->objects[$calendarId]['privat.ics'])
        || !isset($backend->objects[$calendarId]['adcalendar-shift-99.ics'])) {
        throw new RuntimeException('Abgleich entfernt fremde Einträge oder bewahrt veraltete AD-Dienste.');
    }
    try {
        $publisher->publish($shift(99));
        throw new RuntimeException('Fremdes Objekt mit reservierter Dienst-URI wurde überschrieben.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fremdes Objekt mit reservierter Dienst-URI wurde überschrieben.') throw $error;
    }
    if (($backend->objects[$calendarId]['adcalendar-shift-99.ics']['calendardata'] ?? '') !== 'fremd') {
        throw new RuntimeException('Fremdes Objekt mit reservierter Dienst-URI wurde verändert.');
    }

    $publisher->remove('sync-person', 7);
    if (isset($backend->objects[$calendarId]['adcalendar-shift-7.ics'])) throw new RuntimeException('Gelöschter Dienst bleibt im Zielkalender.');
    $publisher->removeCalendar('sync-person');
    if (!isset($backend->calendars[$calendarId]) || !isset($backend->objects[$calendarId]['privat.ics'])) {
        throw new RuntimeException('Deaktivierung löscht einen Kalender mit fremden Einträgen.');
    }
    unset($backend->objects[$calendarId]['privat.ics'], $backend->objects[$calendarId]['adcalendar-shift-99.ics']);
    $publisher->removeCalendar('sync-person');
    if (isset($backend->calendars[$calendarId]) || ($backend->deletedCalendars[0][1] ?? null) !== true) {
        throw new RuntimeException('Leerer app-eigener Kalender wurde nicht dauerhaft entfernt.');
    }

    $collisionBackend = new CalDavBackend();
    $uri = 'adcalendar-dienste-' . substr(hash('sha256', 'sync-person'), 0, 16);
    $collisionBackend->calendars[42] = ['id' => 42, 'uri' => $uri, 'principaluri' => 'principals/users/sync-person', '{DAV:}displayname' => 'Privat'];
    $collisionBackend->objects[42] = [];
    try {
        (new NextcloudDavShiftCalendarPublisher($collisionBackend, new ShiftCalendarEventSerializer()))->publish($shift(7));
        throw new RuntimeException('Fremder Kalender mit kollidierender URI wurde übernommen.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fremder Kalender mit kollidierender URI wurde übernommen.') throw $error;
    }

    echo "NextcloudDavShiftCalendarPublisherTest: OK\n";
}
