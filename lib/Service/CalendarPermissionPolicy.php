<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

/** Zweck: Bewertet die fachliche Schreibmatrix ohne Nextcloud-Infrastruktur. */
final class CalendarPermissionPolicy {
    public function __construct(private CalendarHierarchyPolicy $hierarchy) {}

    public function canManage(string $actorUid, bool $isAdmin, array $actorGroups, string $targetUid, array $targetGroups, array $peerEditGroups = []): bool {
        if ($isAdmin || $actorUid === $targetUid) return true;
        if ($this->hierarchy->manages($actorGroups, $targetGroups)) return true;
        if ($this->hierarchy->targetIsSuperior($actorGroups, $targetGroups)) return false;
        foreach ($peerEditGroups as $group) {
            if (!in_array($group, $actorGroups, true) || !in_array($group, $targetGroups, true)) continue;
            if (in_array($group, [CalendarAccessService::ROLE_OFFICE, CalendarAccessService::ROLE_EB], true)) {
                $actorAreas = array_filter($actorGroups, static fn(string $id): bool => str_starts_with($id, CalendarAccessService::AREA_PREFIX));
                $targetAreas = array_filter($targetGroups, static fn(string $id): bool => str_starts_with($id, CalendarAccessService::AREA_PREFIX));
                if (array_intersect($actorAreas, $targetAreas) !== []) return true;
                continue;
            }
            return true;
        }
        if (array_intersect([CalendarAccessService::ROLE_OFFICE, CalendarAccessService::ROLE_EB], $targetGroups) === []) return false;
        $isOfficeLeader = array_intersect([CalendarHierarchyPolicy::BL, CalendarHierarchyPolicy::DEPUT_BL], $actorGroups) !== [];
        $actorAreas = array_filter($actorGroups, static fn(string $id): bool => str_starts_with($id, CalendarAccessService::AREA_PREFIX));
        $targetAreas = array_filter($targetGroups, static fn(string $id): bool => str_starts_with($id, CalendarAccessService::AREA_PREFIX));
        if ($isOfficeLeader && array_intersect($actorAreas, $targetAreas) !== []) return true;
        return false;
    }
}
