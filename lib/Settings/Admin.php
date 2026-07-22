<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Settings;

use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
use OCA\AdCalendar\Service\ShiftCalendarReconciliationStatusService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IDateTimeFormatter;
use OCP\Settings\ISettings;

/** Zweck: Bindet app-spezifische Kalenderadministration in den Nextcloud-Adminbereich ein. */
final class Admin implements ISettings {
    public function __construct(
        private ShiftCalendarReconciliationStatusService $calendarSyncStatus,
        private IDateTimeFormatter $dateTimeFormatter,
        private GoogleOAuthService $googleOAuth,
    ) {}

    public function getForm(): TemplateResponse {
        $status = $this->calendarSyncStatus->status();
        $status['lastRunLabel'] = $status['hasRun'] ? $this->dateTimeFormatter->formatDateTime($status['lastRunAt']) : 'Noch kein Hintergrundlauf erfasst';
        return new TemplateResponse(Application::APP_ID, 'admin', [
            'calendarSyncStatus' => $status,
            'googleOAuth' => $this->googleOAuth->adminStatus(),
        ]);
    }
    public function getSection(): string { return Application::APP_ID; }
    public function getPriority(): int { return 30; }
}
