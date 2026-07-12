<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

/** Zweck: Bewertet die fachliche Schreibmatrix ohne Nextcloud-Infrastruktur. */
final class CalendarPermissionPolicy {
    public function canManage(string $actorUid, bool $isAdmin, array $actorGroups, string $targetUid, array $targetGroups): bool {
        if ($isAdmin || $actorUid === $targetUid) return true;
        if (in_array('ad-PDL', $actorGroups, true) && in_array(CalendarAccessService::ROLE_PFK, $targetGroups, true)) return true;
        if (array_intersect([CalendarAccessService::ROLE_OFFICE, CalendarAccessService::ROLE_EB], $targetGroups) === []) return false;
        $targetAreas = array_filter($targetGroups, static fn(string $id): bool => str_starts_with($id, CalendarAccessService::AREA_PREFIX));
        foreach ($targetAreas as $area) {
            $leaders = match ($area) {
                'ad-Bereich-Sued' => ['ad-BL-Sued', 'ad-StvBL-Sued'],
                'ad-Bereich-Nordost' => ['ad-BL-Nordost-West', 'ad-StvBL-Nordost'],
                'ad-Bereich-West' => ['ad-BL-Nordost-West', 'ad-StvBL-West'],
                default => [],
            };
            if (array_intersect($leaders, $actorGroups) !== []) return true;
        }
        return false;
    }
}
