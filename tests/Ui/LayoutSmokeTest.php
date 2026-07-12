<?php

declare(strict_types=1);

$template = file_get_contents(__DIR__ . '/../../templates/index.php');
$css = file_get_contents(__DIR__ . '/../../css/style.css');
if ($template === false || $css === false) throw new RuntimeException('UI-Dateien konnten nicht gelesen werden.');
foreach (['<details class="adc-filters">', 'id="adc-filter-status"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Kompakter Filtervertrag fehlt: {$contract}");
}
foreach (['height: 100%', 'min-height: 0', 'overflow-y: auto', 'overflow-x: hidden', 'background: var(--color-main-background)', '.adc-table-wrap { overflow-x: auto', '.adc-filter-grid'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Scroll-/Layoutvertrag fehlt: {$contract}");
}
echo "LayoutSmokeTest: OK\n";
