<?php

declare(strict_types=1);

$template = file_get_contents(__DIR__ . '/../../templates/index.php');
$css = file_get_contents(__DIR__ . '/../../css/style.css');
$info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
if ($template === false || $css === false || $info === false) throw new RuntimeException('UI-Dateien konnten nicht gelesen werden.');
if (!str_contains($info, '<app>orgsuite</app>') || str_contains($info, '<navigations>')) throw new RuntimeException('OrgSuite-Appvertrag fehlt.');
foreach (['role="tablist"', 'id="adc-tab-calendar"', 'id="adc-tab-settings"', 'id="adc-settings-view"', 'id="adc-shift-defaults-form"', '<details class="adc-filters">', 'id="adc-filter-status"', 'id="adc-save-default"', 'id="adc-reset-selection"', 'Auswahl zurücksetzen', 'id="adc-entry-dialog"', 'id="adc-meeting-dialog"', 'id="adc-meeting-duration"', 'id="adc-meeting-title"', 'class="adc-overview"', 'class="adc-overview-header"', 'class="adc-button-icon icon-calendar-dark" aria-hidden="true"', "script('adcalendar', 'models/organization')", "script('adcalendar', 'modules/calendar-state')", "script('adcalendar', 'modules/entry-workflow')", "script('adcalendar', 'components/calendar-filters')", "script('adcalendar', 'components/calendar-cell')", "script('adcalendar', 'components/entry-dialog')", "script('adcalendar', 'components/meeting-finder')", "script('adcalendar', 'components/shift-defaults')", "script('adcalendar', 'components/tab-navigation')", "script('adcalendar', 'components/week-navigation')", "script('adcalendar', 'components/week-table')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Kompakter Filtervertrag fehlt: {$contract}");
}
foreach (["script('localbase', 'api/api-client')", "script('localbase', 'models/model')", "script('localbase', 'repositories/repository')", "script('localbase', 'ui/ui')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("LocalBase-UI-Vertrag fehlt: {$contract}");
}
foreach (["script('orgsuite', 'suite-navigation')", "style('orgsuite', 'suite-navigation')", 'data-orgsuite data-suite="ad" data-current-app="adcalendar"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Suite-Navigationsvertrag fehlt: {$contract}");
}
if (!str_contains($template, 'erscheinen als feste Dienste im Kalender')) throw new RuntimeException('Standarddienst-Erklaerung fehlt in den Einstellungen.');
foreach (['height: 100%', 'min-height: 0', 'overflow-y: auto', 'overflow-x: hidden', 'background: var(--color-main-background)', '.adc-app [hidden] { display: none !important; }', '.adc-table-wrap { width: 100%; max-width: 100%; min-width: 0; overflow-x: auto', 'width: max-content', 'min-width: 0', 'table-layout: auto', '.adc-filter-grid', '.adc-selection-actions', 'height: auto !important', '.adc-dialog:not([open])', '.adc-quick-add', '.adc-quick-add[data-tooltip]::after', '.adc-meeting-people', '.adc-tabs', '.adc-shift-default-row', '.adc-overview-header', 'white-space: nowrap', '.adc-settings-view { width: 100%; max-width: none', '.adc-entry--blocked { border: 2px solid var(--color-error)', 'background: var(--color-error)', 'color: var(--color-error-text)'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Scroll-/Layoutvertrag fehlt: {$contract}");
}
echo "LayoutSmokeTest: OK\n";
