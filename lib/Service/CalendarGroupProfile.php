<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;

/** Zweck: Leitet sichtbare Fachrollen und nur für BO/EB gültige Bürobereiche aus Nextcloud-Gruppen ab. */
final class CalendarGroupProfile {
    public function __construct(private ?AdOrganizationSettingsService $organization = null) {}

    public function get(array $groupIds): array {
        $definition = $this->definition();
        $roles = array_values(array_intersect($definition->roleGroupIds(static fn(array $role): bool => $role['calendarVisible']), $groupIds));
        $hasAreaRole = array_filter($roles, $definition->roleIsAreaScopedByGroup(...)) !== [];
        $areas = $hasAreaRole
            ? array_values(array_intersect($definition->areaGroupIds(), $groupIds))
            : [];
        $clusters = [];
        foreach ($roles as $role) {
            foreach ($areas ?: [''] as $area) $clusters[] = $area === '' ? $role : $role . '#' . $area;
        }
        return ['roles' => $roles, 'areas' => $areas, 'clusters' => $clusters];
    }

    private function definition(): AdOrganizationDefinition { return $this->organization?->definition() ?? AdOrganizationDefinition::defaults(); }
}
