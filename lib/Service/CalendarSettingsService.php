<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\AdCalendar\AppInfo\Application;
use OCP\IAppConfig;

/** Zweck: Speichert die administrativ aktivierten Peer-Bearbeitungsrechte pro Fachgruppe. */
final class CalendarSettingsService {
    public const PEER_GROUPS = [
        CalendarAccessService::ROLE_OFFICE,
        CalendarAccessService::ROLE_PFK,
        CalendarAccessService::ROLE_EB,
        CalendarAccessService::ROLE_STAFF_HR,
        CalendarAccessService::ROLE_STAFF_QMB,
    ];

    public function __construct(private IAppConfig $config) {}

    /** @return array<string,bool> */
    public function peerEditing(): array {
        $result = [];
        foreach (self::PEER_GROUPS as $group) $result[$group] = $this->config->getValueBool(Application::APP_ID, $this->key($group), false);
        return $result;
    }

    /** @return list<string> */
    public function enabledPeerGroups(): array { return array_keys(array_filter($this->peerEditing())); }

    public function savePeerEditing(array $values): array {
        foreach (self::PEER_GROUPS as $group) $this->config->setValueBool(Application::APP_ID, $this->key($group), filter_var($values[$group] ?? false, FILTER_VALIDATE_BOOL));
        return $this->peerEditing();
    }

    private function key(string $group): string { return 'peer_edit_' . strtolower(str_replace(['ad-', '-'], ['', '_'], $group)); }
}
