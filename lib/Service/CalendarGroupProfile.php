<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

/** Zweck: Leitet sichtbare Fachrollen und nur fuer BO/EB gueltige Buerobereiche aus Nextcloud-Gruppen ab. */
final class CalendarGroupProfile {
    public function get(array $groupIds): array {
        $roles = array_values(array_intersect([
            CalendarAccessService::ROLE_EB,
            CalendarAccessService::ROLE_PFK,
            CalendarAccessService::ROLE_OFFICE,
            CalendarAccessService::ROLE_STAFF_HR,
            CalendarAccessService::ROLE_STAFF_QMB,
            CalendarHierarchyPolicy::GF_AS,
            CalendarHierarchyPolicy::GF_DIGI,
            CalendarHierarchyPolicy::ASSISTANT_GF_DIGI,
            CalendarHierarchyPolicy::FINANCE_LEAD,
            CalendarHierarchyPolicy::FINANCE,
            CalendarHierarchyPolicy::IT,
            CalendarHierarchyPolicy::SECRETARIAT,
            CalendarHierarchyPolicy::PDL,
            CalendarHierarchyPolicy::BL,
            CalendarHierarchyPolicy::DEPUT_BL,
        ], $groupIds));
        $hasAreaRole = array_intersect([CalendarAccessService::ROLE_EB, CalendarAccessService::ROLE_OFFICE, CalendarHierarchyPolicy::BL, CalendarHierarchyPolicy::DEPUT_BL], $roles) !== [];
        $areas = $hasAreaRole
            ? array_values(array_filter($groupIds, static fn(string $id): bool => str_starts_with($id, CalendarAccessService::AREA_PREFIX)))
            : [];
        $clusters = [];
        foreach ($roles as $role) {
            foreach ($areas ?: [''] as $area) $clusters[] = $area === '' ? $role : $role . '#' . $area;
        }
        return ['roles' => $roles, 'areas' => $areas, 'clusters' => $clusters];
    }
}
