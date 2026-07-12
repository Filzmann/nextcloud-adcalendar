<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\IUserManager;

/**
 * Zweck: Erzwingt den gemeinsamen Rollen-/Bereichsvertrag serverseitig.
 * Vertrag: Alle angemeldeten Nutzer*innen lesen; eigene Eintraege und fachlich delegierte Ziele werden bearbeitet.
 */
final class CalendarAccessService {
    public const ROLE_EB = 'ad-EB';
    public const ROLE_PFK = 'ad-PFK';
    public const ROLE_OFFICE = 'ad-Buero';
    public const ROLE_STAFF_HR = 'ad-Stab-HR';
    public const ROLE_STAFF_QMB = 'ad-Stab-QMB';
    public const AREA_PREFIX = 'ad-Bereich-';

    public function __construct(private IGroupManager $groups, private IUserSession $session, private IUserManager $users, private CalendarPermissionPolicy $policy, private CalendarSettingsService $settings) {}

    public function currentUser(): ?IUser { return $this->session->getUser(); }

    public function canView(): bool {
        $user = $this->currentUser();
        return $user !== null;
    }

    public function canManage(string $employeeUid): bool {
        $user = $this->currentUser();
        if ($user === null) return false;
        $target = $this->users->get($employeeUid);
        if ($target === null) return false;
        $actorGroups = $this->groupIds($user);
        $targetGroups = $this->groupIds($target);
        return $this->policy->canManage($user->getUID(), $this->groups->isAdmin($user->getUID()), $actorGroups, $employeeUid, $targetGroups, $this->settings->enabledPeerGroups());
    }

    /** @return list<array{uid:string,displayName:string,roles:list<string>,areas:list<string>,clusters:list<string>}> */
    public function visibleEmployees(): array {
        if (!$this->canView()) return [];
        $byUid = [];
        foreach ([self::ROLE_EB, self::ROLE_PFK, self::ROLE_OFFICE, self::ROLE_STAFF_HR, self::ROLE_STAFF_QMB] as $role) {
            $group = $this->groups->get($role);
            if ($group === null) continue;
            foreach ($group->getUsers() as $user) $byUid[$user->getUID()] = $user;
        }
        $result = [];
        foreach ($byUid as $user) {
            $ids = array_map('strval', $this->groups->getUserGroupIds($user));
            $roles = array_values(array_intersect([self::ROLE_EB, self::ROLE_PFK, self::ROLE_OFFICE, self::ROLE_STAFF_HR, self::ROLE_STAFF_QMB], $ids));
            $areas = array_values(array_filter($ids, static fn(string $id): bool => str_starts_with($id, self::AREA_PREFIX)));
            $clusters = [];
            foreach ($roles as $role) foreach ($areas ?: [''] as $area) $clusters[] = $area === '' ? $role : $role . '#' . $area;
            $result[] = ['uid' => $user->getUID(), 'displayName' => $user->getDisplayName(), 'roles' => $roles, 'areas' => $areas, 'clusters' => $clusters, 'canManage' => $this->canManage($user->getUID())];
        }
        usort($result, static fn(array $a, array $b): int => strnatcasecmp($a['displayName'], $b['displayName']));
        return $result;
    }

    private function inGroup(IUser $user, string $group): bool { return in_array($group, $this->groups->getUserGroupIds($user), true); }
    /** @return list<string> */
    private function groupIds(IUser $user): array { return array_map('strval', $this->groups->getUserGroupIds($user)); }
}
