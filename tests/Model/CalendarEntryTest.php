<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';

use OCA\AdCalendar\Model\CalendarEntry;

$shift = CalendarEntry::get([
    'employeeUid' => 'test-person-1',
    'start' => '2026-07-13T08:00:00+02:00',
    'end' => '2026-07-13T16:00:00+02:00',
    'type' => CalendarEntry::TYPE_SHIFT,
]);
$appointment = CalendarEntry::get([
    'employeeUid' => 'test-person-1',
    'start' => '2026-07-13T10:00:00+02:00',
    'end' => '2026-07-13T11:00:00+02:00',
    'type' => CalendarEntry::TYPE_APPOINTMENT,
    'title' => 'Neutraler Testtermin',
]);

if ($shift->durationMinutes() !== 480 || !$appointment->isWithin($shift)) {
    throw new RuntimeException('Zeitraumvertrag ist verletzt.');
}

$laterShift = CalendarEntry::get(['employeeUid' => 'test-person-1', 'start' => '2026-07-13T15:00:00+02:00', 'end' => '2026-07-13T18:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT]);
$touchingShift = CalendarEntry::get(['employeeUid' => 'test-person-1', 'start' => '2026-07-13T16:00:00+02:00', 'end' => '2026-07-13T18:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT]);
if (!$shift->overlaps($laterShift)) throw new RuntimeException('Echte Dienstueberschneidung wurde nicht erkannt.');
if ($shift->overlaps($touchingShift)) throw new RuntimeException('Direkt anschliessende Dienste wurden als Ueberschneidung behandelt.');

$linked = CalendarEntry::get(array_merge($appointment->toArray(), ['parentEntryId' => 42]));
if ($linked->parentEntryId() !== 42) throw new RuntimeException('Explizite Dienst-Termin-Zuordnung ging verloren.');

try {
    CalendarEntry::get(array_merge($shift->toArray(), ['parentEntryId' => 42]));
    throw new RuntimeException('Dienst mit Parent wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

try {
    CalendarEntry::get([
        'employeeUid' => 'test-person-1',
        'start' => '2026-07-13T10:00:00+02:00',
        'end' => '2026-07-13T11:00:00+02:00',
        'type' => CalendarEntry::TYPE_APPOINTMENT,
    ]);
    throw new RuntimeException('Termin ohne Titel wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

echo "CalendarEntryTest: OK\n";
