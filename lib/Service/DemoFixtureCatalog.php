<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

/** Zweck: Definiert neutrale, vollstaendige Demopersonen fuer jede Kalenderrolle und jeden Buerobereich. */
final class DemoFixtureCatalog {
    /** @return list<array{uid:string,name:string,groups:list<string>}> */
    public function all(): array {
        return [
            ['uid' => 'adc-demo-gf-as', 'name' => 'Alma Adler (GF-AS)', 'groups' => ['ad-GF-AS']],
            ['uid' => 'adc-demo-gf-digi', 'name' => 'David Berger (GF-Digi)', 'groups' => ['ad-GF-Digi']],
            ['uid' => 'adc-demo-pdl', 'name' => 'Paula Lindner (PDL)', 'groups' => ['ad-PDL']],
            ['uid' => 'adc-demo-asdgf-digi', 'name' => 'Alexis Dorn (AsdGF-Digi)', 'groups' => ['ad-AsdGF-Digi']],
            ['uid' => 'adc-demo-finanzleitung', 'name' => 'Leonie Frank (Leitung Finanzen und Lohn)', 'groups' => ['ad-Leitung-Finanzen-Lohn']],
            ['uid' => 'adc-demo-finanzen', 'name' => 'Finn Lohmann (Finanzen und Lohn)', 'groups' => ['ad-Finanzen-Lohn']],
            ['uid' => 'adc-demo-it', 'name' => 'Imani Teich (IT)', 'groups' => ['ad-IT']],
            ['uid' => 'adc-demo-sekretariat', 'name' => 'Samira König (Sekretariat)', 'groups' => ['ad-Sekretariat']],
            ['uid' => 'adc-demo-hr', 'name' => 'Hanna Reuter (Stab HR)', 'groups' => ['ad-Stab-HR']],
            ['uid' => 'adc-demo-qmb', 'name' => 'Quinn Meyer (Stab QMB)', 'groups' => ['ad-Stab-QMB']],
            ['uid' => 'adc-demo-bl-now', 'name' => 'Nora Winter (Büro Nordost/West, BL)', 'groups' => ['ad-BL', 'ad-Buero', 'ad-Bereich-Nordost', 'ad-Bereich-West']],
            ['uid' => 'adc-demo-bl-sued', 'name' => 'Sofia Kern (Büro Süd, BL)', 'groups' => ['ad-BL', 'ad-Buero', 'ad-Bereich-Sued']],
            ['uid' => 'adc-demo-stvbl-no', 'name' => 'Nele Hartmann (EB Nordost, Stv. BL)', 'groups' => ['ad-StvBL', 'ad-EB', 'ad-Bereich-Nordost']],
            ['uid' => 'adc-demo-stvbl-west', 'name' => 'Wiebke Hahn (EB West, Stv. BL)', 'groups' => ['ad-StvBL', 'ad-EB', 'ad-Bereich-West']],
            ['uid' => 'adc-demo-stvbl-sued', 'name' => 'Sina Maurer (EB Süd, Stv. BL)', 'groups' => ['ad-StvBL', 'ad-EB', 'ad-Bereich-Sued']],
            ['uid' => 'adc-demo-bo-no', 'name' => 'Mara Brandt (Büro Nordost)', 'groups' => ['ad-Buero', 'ad-Bereich-Nordost']],
            ['uid' => 'adc-demo-bo-west', 'name' => 'Mika Werner (Büro West)', 'groups' => ['ad-Buero', 'ad-Bereich-West']],
            ['uid' => 'adc-demo-bo-sued', 'name' => 'Selin Krüger (Büro Süd)', 'groups' => ['ad-Buero', 'ad-Bereich-Sued']],
            ['uid' => 'adc-demo-eb-no', 'name' => 'Enna Busch (EB Nordost)', 'groups' => ['ad-EB', 'ad-Bereich-Nordost']],
            ['uid' => 'adc-demo-eb-west', 'name' => 'Emil Weber (EB West)', 'groups' => ['ad-EB', 'ad-Bereich-West']],
            ['uid' => 'adc-demo-eb-sued', 'name' => 'Eda Sommer (EB Süd)', 'groups' => ['ad-EB', 'ad-Bereich-Sued']],
            ['uid' => 'adc-demo-pfk-a', 'name' => 'Petra Falk (PFK)', 'groups' => ['ad-PFK']],
            ['uid' => 'adc-demo-pfk-b', 'name' => 'Robin Keller (PFK)', 'groups' => ['ad-PFK']],
        ];
    }
}
