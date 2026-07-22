<?php

declare(strict_types=1);

namespace Psr\Log { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
namespace OCA\AdCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore { public function connections(string $uid): array { return ['kopano' => ['serverUrl' => 'fail'], 'manual' => ['serverUrl' => 'ok']]; } public function connection(string $uid, string $provider): ?array { return $this->connections($uid)[$provider] ?? null; } }
    final class CalDavClient { public array $published = []; public function publish(array $connection, \OCA\AdCalendar\Model\CalendarEntry $shift): void { $this->published[] = $connection['serverUrl']; if ($connection['serverUrl'] === 'fail') throw new \RuntimeException('nicht erreichbar'); } public function replaceAll(array $connection, array $shifts): void {} public function remove(array $connection, int $id): void {} public function removeCalendar(array $connection): void {} }
    final class GoogleCalendarClient { public function publish(string $uid, array $connection, \OCA\AdCalendar\Model\CalendarEntry $shift): void {} public function replaceAll(string $uid, array $connection, array $shifts): void {} public function remove(string $uid, array $connection, int $id): void {} public function removeCalendar(string $uid, array $connection): void {} }
}

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ExternalShiftCalendarPublisher.php';

    use OCA\AdCalendar\CalendarSync\CalDavClient;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\CalendarSync\ExternalShiftCalendarPublisher;
    use OCA\AdCalendar\CalendarSync\GoogleCalendarClient;
    use OCA\AdCalendar\Model\CalendarEntry;
    use Psr\Log\LoggerInterface;

    $dav = new CalDavClient();
    $logger = new class implements LoggerInterface { public array $errors = []; public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; } };
    $publisher = new ExternalShiftCalendarPublisher(new ExternalCalendarConnectionStore(), $dav, new GoogleCalendarClient(), $logger);
    $shift = CalendarEntry::get(['id' => 61, 'employeeUid' => 'person-a', 'start' => '2026-07-22T08:00:00+02:00', 'end' => '2026-07-22T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '']);
    try { $publisher->publish($shift); throw new RuntimeException('Providerfehler wurde verschluckt.'); }
    catch (RuntimeException $error) { if ($error->getMessage() === 'Providerfehler wurde verschluckt.') throw $error; }
    if ($dav->published !== ['fail', 'ok']) throw new RuntimeException('Ein Providerfehler blockiert nachfolgende Verbindungen.');
    if (count($logger->errors) !== 1 || str_contains(json_encode($logger->errors), 'person-a')) throw new RuntimeException('Providerfehler wird nicht datensparsam protokolliert.');

    echo "ExternalShiftCalendarPublisherTest: OK\n";
}
