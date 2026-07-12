<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Service/CalendarAccessService.php';
require_once __DIR__ . '/../../lib/Service/CalendarPermissionPolicy.php';

use OCA\AdCalendar\Service\CalendarPermissionPolicy;

$policy = new CalendarPermissionPolicy();
$assert = static function (bool $expected, bool $actual, string $message): void { if ($expected !== $actual) throw new RuntimeException($message); };

$assert(true, $policy->canManage('a', false, [], 'a', []), 'Eigene Eintraege muessen bearbeitbar sein.');
$assert(true, $policy->canManage('pdl', false, ['ad-PDL'], 'pfk', ['ad-PFK']), 'PDL muss PFK bearbeiten duerfen.');
$assert(false, $policy->canManage('pdl', false, ['ad-PDL'], 'eb', ['ad-EB', 'ad-Bereich-West']), 'PDL darf EB nicht bearbeiten.');
$assert(true, $policy->canManage('bl', false, ['ad-BL-Nordost-West'], 'eb', ['ad-EB', 'ad-Bereich-West']), 'Gemeinsame BL muss West bearbeiten duerfen.');
$assert(true, $policy->canManage('stv', false, ['ad-StvBL-Nordost'], 'buero', ['ad-Buero', 'ad-Bereich-Nordost']), 'StvBL muss eigenen Bereich bearbeiten duerfen.');
$assert(false, $policy->canManage('stv', false, ['ad-StvBL-Nordost'], 'buero', ['ad-Buero', 'ad-Bereich-West']), 'StvBL darf fremden Bereich nicht bearbeiten.');
$assert(false, $policy->canManage('bl', false, ['ad-BL-Sued'], 'hr', ['ad-Stab-HR']), 'BL darf Stab ohne Delegation nicht bearbeiten.');
$assert(true, $policy->canManage('admin', true, [], 'hr', ['ad-Stab-HR']), 'Admin muss alle bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['ad-Buero'], 'bo-b', ['ad-Buero']), 'Peer-Bearbeitung muss standardmaessig aus sein.');
$assert(true, $policy->canManage('bo-a', false, ['ad-Buero', 'ad-Bereich-Sued'], 'bo-b', ['ad-Buero', 'ad-Bereich-Sued'], ['ad-Buero']), 'Aktivierte BO-Peers im selben Buero muessen einander bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['ad-Buero', 'ad-Bereich-Sued'], 'bo-b', ['ad-Buero', 'ad-Bereich-Nordost'], ['ad-Buero']), 'BO-Peer-Recht darf kein anderes Buero oeffnen.');
$assert(false, $policy->canManage('eb-a', false, ['ad-EB'], 'eb-b', ['ad-EB'], ['ad-EB']), 'Bereichsgebundene Peers ohne gemeinsames Buero duerfen einander nicht bearbeiten.');
$assert(true, $policy->canManage('pfk-a', false, ['ad-PFK'], 'pfk-b', ['ad-PFK'], ['ad-PFK']), 'PFK-Peer-Recht bleibt mangels Buerobereich fachgruppenweit.');
$assert(false, $policy->canManage('pfk', false, ['ad-PFK'], 'bo', ['ad-Buero'], ['ad-PFK']), 'Peer-Recht darf keine andere Zielgruppe oeffnen.');

echo "CalendarPermissionPolicyTest: OK\n";
