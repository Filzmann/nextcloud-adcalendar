<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Service/DemoFixtureCatalog.php';

use OCA\AdCalendar\Service\DemoFixtureCatalog;

$fixtures = (new DemoFixtureCatalog())->all();
if (count($fixtures) < 20) throw new RuntimeException('Der Demokatalog deckt die Organisationsstruktur nicht ausreichend ab.');
$requiredGroups = [
    'ad-EB', 'ad-PFK', 'ad-Buero', 'ad-Stab-HR', 'ad-Stab-QMB', 'ad-GF-AS', 'ad-GF-Digi',
    'ad-AsdGF-Digi', 'ad-Leitung-Finanzen-Lohn', 'ad-Finanzen-Lohn', 'ad-IT', 'ad-Sekretariat',
    'ad-PDL', 'ad-BL', 'ad-StvBL', 'ad-Bereich-Nordost', 'ad-Bereich-West', 'ad-Bereich-Sued',
];
$covered = array_fill_keys(array_merge(...array_column($fixtures, 'groups')), true);
foreach ($requiredGroups as $group) {
    if (!isset($covered[$group])) throw new RuntimeException("Demodatensatz fuer {$group} fehlt.");
}
$uids = [];
foreach ($fixtures as $fixture) {
    if (isset($uids[$fixture['uid']])) throw new RuntimeException("Doppelte Demo-UID: {$fixture['uid']}");
    $uids[$fixture['uid']] = true;
    if (preg_match('/^[^()]+ \([^)]+\)$/', $fixture['name']) !== 1) throw new RuntimeException("Demoperson ohne Name und Gruppenklammer: {$fixture['uid']}");
}

$source = file_get_contents(__DIR__ . '/../../lib/Command/SeedDemoCommand.php');
if ($source === false) throw new RuntimeException('SeedDemoCommand konnte nicht gelesen werden.');
foreach (['ensureUser', 'createGroup', 'setDisplayName', 'existsCreatedByForEmployee', "'parentEntryId' => \$shiftId"] as $contract) {
    if (!str_contains($source, $contract)) throw new RuntimeException("Demo-Vertrag fehlt: {$contract}");
}
echo "SeedDemoCommandSmokeTest: OK\n";
