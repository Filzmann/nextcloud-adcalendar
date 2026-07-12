<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../../lib/Controller/ApiController.php');
if ($source === false) throw new RuntimeException('ApiController konnte nicht gelesen werden.');

foreach (['create', 'update', 'delete'] as $method) {
    if (!preg_match('/#\[NoAdminRequired\]\s+public function ' . $method . '\b/s', $source)) {
        throw new RuntimeException("{$method} ist nicht als angemeldeter API-Pfad deklariert.");
    }
}
if (preg_match('/#\[NoCSRFRequired\]\s+#\[NoAdminRequired\]\s+public function (create|update|delete)\b/s', $source)) {
    throw new RuntimeException('Ein schreibender Endpunkt umgeht CSRF-Schutz.');
}
foreach (['canView()', 'canManage('] as $guard) {
    if (!str_contains($source, $guard)) throw new RuntimeException("Berechtigungspruefung {$guard} fehlt.");
}

echo "ControllerSecuritySmokeTest: OK\n";
