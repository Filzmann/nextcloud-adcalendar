<?php

declare(strict_types=1);

$template = file_get_contents(__DIR__ . '/../../templates/index.php');
$css = file_get_contents(__DIR__ . '/../../css/style.css');
if ($template === false || $css === false) throw new RuntimeException('UI-Dateien konnten nicht gelesen werden.');
foreach (['role="tablist"', 'id="adc-tab-calendar"', 'id="adc-tab-settings"', 'id="adc-settings-view"', 'id="adc-shift-defaults-form"', '<details class="adc-filters">', 'id="adc-filter-status"', 'id="adc-save-default"', 'id="adc-entry-dialog"', 'id="adc-meeting-dialog"', 'class="adc-selection-action icon-calendar-dark"', "script('adcalendar', 'modules/calendar-state')", "script('adcalendar', 'components/calendar-cell')", "script('adcalendar', 'components/entry-dialog')", "script('adcalendar', 'components/meeting-finder')", "script('adcalendar', 'components/shift-defaults')", "script('adcalendar', 'components/tab-navigation')", "script('adcalendar', 'components/week-table')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Kompakter Filtervertrag fehlt: {$contract}");
}
foreach (["script('localbase', 'api/api-client')", "script('localbase', 'models/model')", "script('localbase', 'repositories/repository')", "script('localbase', 'ui/ui')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("LocalBase-UI-Vertrag fehlt: {$contract}");
}
foreach (['height: 100%', 'min-height: 0', 'overflow-y: auto', 'overflow-x: hidden', 'background: var(--color-main-background)', '.adc-app [hidden] { display: none !important; }', '.adc-table-wrap { overflow-x: auto', 'width: max-content', 'min-width: 0', 'table-layout: auto', '.adc-filter-grid', 'height: auto !important', '.adc-dialog:not([open])', '.adc-quick-add', '.adc-quick-add[data-tooltip]::after', '.adc-meeting-people', '.adc-tabs', '.adc-shift-default-row'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Scroll-/Layoutvertrag fehlt: {$contract}");
}
echo "LayoutSmokeTest: OK\n";
