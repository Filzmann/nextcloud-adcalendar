<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarEventSerializer.php';

use OCA\AdCalendar\CalendarSync\ShiftCalendarEventSerializer;
use OCA\AdCalendar\Model\CalendarEntry;

$serializer = new ShiftCalendarEventSerializer();
$shift = CalendarEntry::get([
    'id' => 17,
    'employeeUid' => 'sync-person',
    'start' => '2026-07-20T08:00:00+02:00',
    'end' => '2026-07-20T16:00:00+02:00',
    'type' => CalendarEntry::TYPE_SHIFT,
    'title' => "Frühdienst, Büro; Nordost\nHinweis",
]);
$ics = $serializer->serialize($shift, '20260718T090000Z');

foreach ([
    "BEGIN:VCALENDAR\r\n",
    "UID:adcalendar-shift-17@local\r\n",
    "DTSTAMP:20260718T090000Z\r\n",
    "DTSTART:20260720T060000Z\r\n",
    "DTEND:20260720T140000Z\r\n",
    "SUMMARY:Frühdienst\\, Büro\\; Nordost\\nHinweis\r\n",
    "CLASS:PRIVATE\r\n",
    "X-AD-CALENDAR-ENTRY-ID:17\r\n",
    "END:VCALENDAR\r\n",
] as $contract) {
    if (!str_contains($ics, $contract)) throw new RuntimeException("ICS-Vertrag fehlt: {$contract}");
}
if ($serializer->objectUri($shift) !== 'adcalendar-shift-17.ics') throw new RuntimeException('Deterministische Objekt-URI fehlt.');

$untitled = CalendarEntry::get(array_replace($shift->toArray(), ['title' => '']));
if (!str_contains($serializer->serialize($untitled, '20260718T090000Z'), "SUMMARY:Dienst\r\n")) {
    throw new RuntimeException('Titelloser Dienst hat keinen verständlichen Kalendernamen.');
}

$long = CalendarEntry::get(array_replace($shift->toArray(), ['title' => str_repeat('Ä', 60)]));
foreach (explode("\r\n", $serializer->serialize($long, '20260718T090000Z')) as $line) {
    if (strlen($line) > 75) throw new RuntimeException('ICS-Zeile überschreitet 75 Oktette.');
}

try {
    $serializer->serialize(CalendarEntry::get(array_replace($shift->toArray(), ['id' => null])), '20260718T090000Z');
    throw new RuntimeException('Dienst ohne persistente ID wurde serialisiert.');
} catch (InvalidArgumentException) {
}

echo "ShiftCalendarEventSerializerTest: OK\n";
