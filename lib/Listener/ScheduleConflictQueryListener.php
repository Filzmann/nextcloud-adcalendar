<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Listener;

use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\LocalBase\Calendar\ScheduleConflict;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** Zweck: Meldet bestehende Dienste und Termine read-only als Konflikte an Abwesenheitsprovider. */
final class ScheduleConflictQueryListener implements IEventListener {
    public function __construct(private CalendarEntryRepository $entries) {}
    public function handle(Event $event): void { if (!$event instanceof ScheduleConflictQueryEvent) return; foreach ($this->entries->findRange($event->start(),$event->end(),[$event->employeeUid()]) as $entry) $event->add(new ScheduleConflict($entry->type(),$entry->start(),$entry->end(),$entry->type() === CalendarEntry::TYPE_SHIFT ? 'Dienst' : ($entry->title() !== '' ? $entry->title() : 'Termin'))); }
}
