<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Zweck: Erzwingt den gemeinsamen Rollen-/Bereichsvertrag serverseitig.
 * Vertrag: EB plant fuer alle sichtbaren Personen; PFK bearbeitet nur eigene Eintraege.
 */
final class CalendarAccessService {
    public const ROLE_EB = 'ad-EB';
    public const ROLE_PFK = 'ad-PFK';
    public const AREA_PREFIX = 'ad-Bereich-';

    public function __construct(private IGroupManager $groups, private IUserSession $session) {}

    public function currentUser(): ?IUser { return $this->session->getUser(); }

    public function canView(): bool {
        $user = $this->currentUser();
        return $user !== null && ($this->groups->isAdmin($user->getUID()) || $this->hasRole($user));
    }

    public function canManage(string $employeeUid): bool {
        $user = $this->currentUser();
        if ($user === null) return false;
        return $this->groups->isAdmin($user->getUID())
            || $this->inGroup($user, self::ROLE_EB)
            || ($user->getUID() === $employeeUid && $this->inGroup($user, self::ROLE_PFK));
    }

    /** @return list<array{uid:string,displayName:string,roles:list<string>,areas:list<string>,clusters:list<string>}> */
    public function visibleEmployees(): array {
        if (!$this->canView()) return [];
        $byUid = [];
        foreach ([self::ROLE_EB, self::ROLE_PFK] as $role) {
            $group = $this->groups->get($role);
            if ($group === null) continue;
            foreach ($group->getUsers() as $user) $byUid[$user->getUID()] = $user;
        }
        $result = [];
        foreach ($byUid as $user) {
            $ids = array_map('strval', $this->groups->getUserGroupIds($user));
            $roles = array_values(array_intersect([self::ROLE_EB, self::ROLE_PFK], $ids));
            $areas = array_values(array_filter($ids, static fn(string $id): bool => str_starts_with($id, self::AREA_PREFIX)));
            $clusters = [];
            foreach ($roles as $role) foreach ($areas ?: [''] as $area) $clusters[] = $area === '' ? $role : $role . '#' . $area;
            $result[] = ['uid' => $user->getUID(), 'displayName' => $user->getDisplayName(), 'roles' => $roles, 'areas' => $areas, 'clusters' => $clusters];
        }
        usort($result, static fn(array $a, array $b): int => strnatcasecmp($a['displayName'], $b['displayName']));
        return $result;
    }

    private function hasRole(IUser $user): bool { return $this->inGroup($user, self::ROLE_EB) || $this->inGroup($user, self::ROLE_PFK); }
    private function inGroup(IUser $user, string $group): bool { return in_array($group, $this->groups->getUserGroupIds($user), true); }
}
