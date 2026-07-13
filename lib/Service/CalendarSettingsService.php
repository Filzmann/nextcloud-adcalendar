<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\AdCalendar\AppInfo\Application;
use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;
use OCP\IAppConfig;

/** Zweck: Speichert die administrativ aktivierten Peer-Bearbeitungsrechte pro Fachgruppe. */
final class CalendarSettingsService {
    public function __construct(private IAppConfig $config, private ?AdOrganizationSettingsService $organization = null) {}

    /** @return array<string,bool> */
    public function peerEditing(): array {
        $result = [];
        foreach ($this->peerGroups() as $group) $result[$group] = $this->config->getValueBool(Application::APP_ID, $this->key($group), false);
        return $result;
    }

    /** @return list<string> */
    public function enabledPeerGroups(): array { return array_keys(array_filter($this->peerEditing())); }

    public function savePeerEditing(array $values): array {
        foreach ($this->peerGroups() as $group) $this->config->setValueBool(Application::APP_ID, $this->key($group), filter_var($values[$group] ?? false, FILTER_VALIDATE_BOOL));
        return $this->peerEditing();
    }

    public function peerOptions(): array {
        $definition = $this->definition();
        return array_map(static fn(string $group): array => ['groupId' => $group, 'label' => $definition->roleLabelForGroup($group)], $this->peerGroups());
    }

    public function organization(): array { return $this->definition()->toArray(); }

    public function saveOrganization(array $data): array {
        if ($this->organization === null) throw new \LogicException('Der gemeinsame Organisationseinstellungsservice ist nicht verfügbar.');
        return $this->organization->save($data)->toArray();
    }

    private function peerGroups(): array { return $this->definition()->roleGroupIds(static fn(array $role): bool => $role['peerEnabled']); }
    private function definition(): AdOrganizationDefinition { return $this->organization?->definition() ?? AdOrganizationDefinition::defaults(); }

    private function key(string $group): string { return 'peer_edit_' . strtolower(str_replace(['ad-', '-'], ['', '_'], $group)); }
}
