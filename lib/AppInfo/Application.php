<?php

declare(strict_types=1);

namespace OCA\AdCalendar\AppInfo;

use OCA\AdCalendar\Listener\ScheduleConflictQueryListener;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'adcalendar';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }
    public function register(IRegistrationContext $context): void { $context->registerEventListener(ScheduleConflictQueryEvent::class, ScheduleConflictQueryListener::class); }
    public function boot(IBootContext $context): void {}
}
