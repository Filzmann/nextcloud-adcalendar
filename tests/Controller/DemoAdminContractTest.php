<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = file_get_contents($root . '/appinfo/routes.php');
$info = file_get_contents($root . '/appinfo/info.xml');
$controller = file_get_contents($root . '/lib/Controller/DemoAdminController.php');
$template = file_get_contents($root . '/templates/admin.php');
$script = file_get_contents($root . '/js/admin.js');
if ($routes === false || $info === false || $controller === false || $template === false || $script === false) {
    throw new RuntimeException('Kalender-Demo-Adminbestandteile fehlen.');
}
foreach (['/api/admin/demo-pack/install', "'verb' => 'POST'"] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("Demo-Route fehlt: {$contract}");
foreach (['<admin>OCA\\AdCalendar\\Settings\\Admin</admin>', '<admin-section>OCA\\AdCalendar\\Settings\\AdminSection</admin-section>'] as $contract) if (!str_contains($info, $contract)) throw new RuntimeException("Adminregistrierung fehlt: {$contract}");
foreach (['CalendarDemoPackService', 'private function isAdmin()', '$this->groups->isAdmin(', 'Http::STATUS_FORBIDDEN'] as $contract) if (!str_contains($controller, $contract)) throw new RuntimeException("Serverseitiger Demo-Adminschutz fehlt: {$contract}");
if (str_contains($controller, 'NoCSRFRequired')) throw new RuntimeException('Demo-Installation darf den CSRF-Schutz nicht umgehen.');
foreach (['id="adc-demo-confirm"', 'id="adc-demo-install"', 'nicht automatisch', 'Dienstkalender-Abgleich', 'calendarSyncStatus', 'Keine Konten- oder Kalenderkennungen'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Demo- oder DAV-Adminoberfläche fehlt: {$contract}");
foreach (['adc-demo-confirm', 'adc-demo-install', "client.request('/api/admin/demo-pack/install'"] as $contract) if (!str_contains($script, $contract)) throw new RuntimeException("Demo-Admininteraktion fehlt: {$contract}");

$settings = file_get_contents($root . '/lib/Settings/Admin.php');
foreach (['ShiftCalendarReconciliationStatusService', 'IDateTimeFormatter', "'calendarSyncStatus'", "'lastRunLabel'"] as $contract) if ($settings === false || !str_contains($settings, $contract)) throw new RuntimeException("Aggregierter DAV-Adminstatus fehlt: {$contract}");

echo "DemoAdminContractTest: OK\n";
