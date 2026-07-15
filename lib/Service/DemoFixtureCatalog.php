<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;
use OCA\LocalBase\Service\AdDemoFixtureCatalog;

/** Zweck: Hält den bisherigen Kalender-Payload stabil und delegiert die gemeinsamen Demopersonen an LocalBase. */
final class DemoFixtureCatalog {
    public function __construct(
        private ?AdOrganizationSettingsService $organization = null,
        private ?AdOrganizationDefinition $override = null,
        private ?AdDemoFixtureCatalog $shared = null,
    ) {}

    /** @return list<array{uid:string,name:string,groups:list<string>}> */
    public function all(): array {
        $fixtures = $this->override !== null
            ? (new AdDemoFixtureCatalog(null, $this->override))->all()
            : ($this->shared ?? new AdDemoFixtureCatalog($this->organization))->all();

        return array_map(static fn(array $fixture): array => [
            'uid' => $fixture['uid'],
            'name' => $fixture['displayName'],
            'groups' => $fixture['groups'],
        ], $fixtures);
    }
}
