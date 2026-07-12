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

echo "CalendarPermissionPolicyTest: OK\n";
