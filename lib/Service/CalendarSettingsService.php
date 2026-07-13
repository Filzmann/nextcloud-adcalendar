<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;
use OCA\LocalBase\Organization\AdSuiteAdminSettingsService;

/** Zweck: Stellt dem Kalender den gemeinsamen Organisations- und Rechtevertrag read-only bereit. */
final class CalendarSettingsService {
    public function __construct(
        private AdSuiteAdminSettingsService $adminSettings,
        private ?AdOrganizationSettingsService $organization = null,
    ) {}

    /** @return array<string,bool> */
    public function peerEditing(): array {
        return $this->adminSettings->calendarPeerEditing();
    }

    /** @return list<string> */
    public function enabledPeerGroups(): array { return $this->adminSettings->enabledCalendarPeerGroups(); }

    public function organization(): array { return $this->definition()->toArray(); }

    private function definition(): AdOrganizationDefinition { return $this->organization?->definition() ?? AdOrganizationDefinition::defaults(); }
}
