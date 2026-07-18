<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use OCA\AdCalendar\Model\CalendarEntry;

/**
 * Zweck: Kapselt die ausgehende Veröffentlichung von AD-Diensten hinter einer providerneutralen Grenze.
 * Vertrag: Implementierungen verändern ausschließlich app-eigene Dienstobjekte; AD Kalender bleibt Quelle der Wahrheit.
 */
interface ShiftCalendarPublisher {
    public const CALENDAR_NAME = 'AD Dienste';

    /** @param list<CalendarEntry> $shifts */
    public function replaceAll(string $employeeUid, array $shifts): void;
    public function publish(CalendarEntry $shift): void;
    public function remove(string $employeeUid, int $shiftId): void;
    public function removeCalendar(string $employeeUid): void;
}
