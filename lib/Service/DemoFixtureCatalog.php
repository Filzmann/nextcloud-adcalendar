<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;

/** Zweck: Erzeugt neutrale Demopersonen aus fachlichen Rollenschlüsseln und der aktuell konfigurierten Organisation. */
final class DemoFixtureCatalog {
    public function __construct(private ?AdOrganizationSettingsService $organization = null, private ?AdOrganizationDefinition $override = null) {}

    /** @return list<array{uid:string,name:string,groups:list<string>}> */
    public function all(): array {
        $definition = $this->override ?? $this->organization?->definition() ?? AdOrganizationDefinition::defaults();
        return array_map(fn(array $fixture): array => [
            'uid' => $fixture['uid'],
            'name' => $fixture['name'],
            'groups' => array_values(array_filter(array_merge(
                array_map($definition->roleGroupId(...), $fixture['roles']),
                array_map($definition->areaGroupId(...), $fixture['areas']),
            ))),
        ], $this->fixtures());
    }

    private function fixtures(): array {
        return [
            ['uid' => 'adc-demo-gf-as', 'name' => 'Alma Adler (GF-AS)', 'roles' => ['gf_as'], 'areas' => []],
            ['uid' => 'adc-demo-gf-digi', 'name' => 'David Berger (GF-Digi)', 'roles' => ['gf_digi'], 'areas' => []],
            ['uid' => 'adc-demo-pdl', 'name' => 'Paula Lindner (PDL)', 'roles' => ['pdl'], 'areas' => []],
            ['uid' => 'adc-demo-asdgf-digi', 'name' => 'Alexis Dorn (AsdGF-Digi)', 'roles' => ['assistant_gf_digi'], 'areas' => []],
            ['uid' => 'adc-demo-finanzleitung', 'name' => 'Leonie Frank (Leitung Finanzen und Lohn)', 'roles' => ['finance_lead'], 'areas' => []],
            ['uid' => 'adc-demo-finanzen', 'name' => 'Finn Lohmann (Finanzen und Lohn)', 'roles' => ['finance'], 'areas' => []],
            ['uid' => 'adc-demo-it', 'name' => 'Imani Teich (IT)', 'roles' => ['it'], 'areas' => []],
            ['uid' => 'adc-demo-sekretariat', 'name' => 'Samira König (Sekretariat)', 'roles' => ['secretariat'], 'areas' => []],
            ['uid' => 'adc-demo-hr', 'name' => 'Hanna Reuter (Stabsstelle HR)', 'roles' => ['staff_hr'], 'areas' => []],
            ['uid' => 'adc-demo-qmb', 'name' => 'Quinn Meyer (Stabsstelle Qualitätsmanagement)', 'roles' => ['staff_qmb'], 'areas' => []],
            ['uid' => 'adc-demo-bl-now', 'name' => 'Nora Winter (Büro Nordost und West, BL)', 'roles' => ['bl', 'office'], 'areas' => ['northeast', 'west']],
            ['uid' => 'adc-demo-bl-sued', 'name' => 'Sofia Kern (Büro Süd, BL)', 'roles' => ['bl', 'office'], 'areas' => ['south']],
            ['uid' => 'adc-demo-stvbl-no', 'name' => 'Nele Hartmann (EB Nordost, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['northeast']],
            ['uid' => 'adc-demo-stvbl-west', 'name' => 'Wiebke Hahn (EB West, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['west']],
            ['uid' => 'adc-demo-stvbl-sued', 'name' => 'Sina Maurer (EB Süd, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['south']],
            ['uid' => 'adc-demo-bo-no', 'name' => 'Mara Brandt (Büro Nordost)', 'roles' => ['office'], 'areas' => ['northeast']],
            ['uid' => 'adc-demo-bo-west', 'name' => 'Mika Werner (Büro West)', 'roles' => ['office'], 'areas' => ['west']],
            ['uid' => 'adc-demo-bo-sued', 'name' => 'Selin Krüger (Büro Süd)', 'roles' => ['office'], 'areas' => ['south']],
            ['uid' => 'adc-demo-eb-no', 'name' => 'Enna Busch (EB Nordost)', 'roles' => ['eb'], 'areas' => ['northeast']],
            ['uid' => 'adc-demo-eb-west', 'name' => 'Emil Weber (EB West)', 'roles' => ['eb'], 'areas' => ['west']],
            ['uid' => 'adc-demo-eb-sued', 'name' => 'Eda Sommer (EB Süd)', 'roles' => ['eb'], 'areas' => ['south']],
            ['uid' => 'adc-demo-pfk-a', 'name' => 'Petra Falk (PFK)', 'roles' => ['pfk'], 'areas' => []],
            ['uid' => 'adc-demo-pfk-b', 'name' => 'Robin Keller (PFK)', 'roles' => ['pfk'], 'areas' => []],
        ];
    }
}
