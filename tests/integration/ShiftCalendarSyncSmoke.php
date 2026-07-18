<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/lib/base.php';

use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Service\CalendarService;
use OCA\AdCalendar\Service\ShiftCalendarSyncService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUserManager;
use Sabre\VObject\Reader;

/**
 * Zweck: Prüft den einseitigen Dienstabgleich gegen den realen internen Nextcloud-DAV-Adapter.
 * Vertrag: Opt-in erzeugt den privaten Kalender; Änderungen und Löschungen folgen der führenden AD-Datenquelle.
 * Datenschutz: Der Test verwendet ausschließlich ein temporäres synthetisches Konto und räumt alle Daten auf.
 */

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$users = \OCP\Server::get(IUserManager::class);
$entries = \OCP\Server::get(CalendarEntryRepository::class);
$calendar = \OCP\Server::get(CalendarService::class);
$sync = \OCP\Server::get(ShiftCalendarSyncService::class);
$publisher = \OCP\Server::get(ShiftCalendarPublisher::class);
$backend = \OCP\Server::get(CalDavBackend::class);

$uid = 'adc-dav-smoke-' . bin2hex(random_bytes(5));
$user = $users->createUser($uid, bin2hex(random_bytes(24)));
if ($user === null) throw new RuntimeException('Temporäres DAV-Integrationskonto konnte nicht angelegt werden.');

$principal = 'principals/users/' . $uid;
$calendarId = null;
$entryId = null;
$start = new DateTimeImmutable('tomorrow 09:00:00', new DateTimeZone('UTC'));
$end = $start->modify('+8 hours');

$findCalendar = static function () use ($backend, $principal): ?array {
    foreach ($backend->getCalendarsForUser($principal) as $candidate) {
        if (($candidate['{DAV:}displayname'] ?? '') === ShiftCalendarPublisher::CALENDAR_NAME) return $candidate;
    }
    return null;
};

try {
    $entryId = $calendar->save([
        'employeeUid' => $uid,
        'start' => $start->format(DATE_ATOM),
        'end' => $end->format(DATE_ATOM),
        'type' => 'shift',
        'title' => 'Synthetischer DAV-Dienst',
    ], null, $uid);
    $assert($findCalendar() === null, 'Ohne persönliches Opt-in wurde bereits ein DAV-Kalender angelegt.');

    $status = $sync->configure($uid, true);
    $assert(($status['enabled'] ?? false) === true, 'Persönliches DAV-Opt-in wurde nicht aktiviert.');
    $createdCalendar = $findCalendar();
    $assert($createdCalendar !== null, 'Der private Kalender „AD Dienste“ wurde nicht angelegt.');
    $calendarId = (int)$createdCalendar['id'];

    $uri = 'adcalendar-shift-' . $entryId . '.ics';
    $object = $backend->getCalendarObject($calendarId, $uri);
    $assert($object !== null, 'Der vorhandene AD-Dienst wurde beim Opt-in nicht veröffentlicht.');
    $vcalendar = Reader::read((string)$object['calendardata']);
    $assert((string)$vcalendar->VEVENT->SUMMARY === 'Synthetischer DAV-Dienst', 'Der veröffentlichte DAV-Titel ist falsch.');

    $calendar->save([
        'employeeUid' => $uid,
        'start' => $start->format(DATE_ATOM),
        'end' => $end->modify('+30 minutes')->format(DATE_ATOM),
        'type' => 'shift',
        'title' => 'Aktualisierter DAV-Dienst',
    ], $entryId, $uid);
    $updated = $backend->getCalendarObject($calendarId, $uri);
    $assert($updated !== null, 'Der aktualisierte AD-Dienst fehlt im DAV-Kalender.');
    $vcalendar = Reader::read((string)$updated['calendardata']);
    $assert((string)$vcalendar->VEVENT->SUMMARY === 'Aktualisierter DAV-Dienst', 'Die DAV-Aktualisierung wurde nicht übernommen.');

    $calendar->delete($entryId, '');
    $entryId = null;
    $assert($backend->getCalendarObject($calendarId, $uri) === null, 'Der gelöschte AD-Dienst blieb im DAV-Kalender erhalten.');

    $status = $sync->configure($uid, false);
    $calendarId = null;
    $assert(($status['enabled'] ?? true) === false, 'Persönliches DAV-Opt-in wurde nicht deaktiviert.');
    $assert($findCalendar() === null, 'Der leere private AD-Kalender wurde beim Opt-out nicht entfernt.');

    echo "AD Kalender/DAV DDEV-Integration: OK\n";
} finally {
    if ($entryId !== null && $entries->find($entryId) !== null) $entries->delete($entryId);
    try {
        $publisher->removeCalendar($uid);
    } catch (Throwable) {
        $remainingCalendar = $findCalendar();
        if ($remainingCalendar !== null) $backend->deleteCalendar((int)$remainingCalendar['id'], true);
    }
    $user->delete();
}
