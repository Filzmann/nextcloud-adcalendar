<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Listener;

use OCA\AdCalendar\AppInfo\Application;
use OCA\LocalBase\Integration\AdIntegrationCapabilities;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<IntegrationCapabilityQueryEvent> */
final class IntegrationCapabilityQueryListener implements IEventListener {
    public function handle(Event $event): void {
        if (!$event instanceof IntegrationCapabilityQueryEvent) return;
        $event->provide(Application::APP_ID, [
            AdIntegrationCapabilities::SCHEDULE_CONFLICT_READ,
            AdIntegrationCapabilities::SCHEDULE_BLOCK_WRITE,
        ]);
    }
}
