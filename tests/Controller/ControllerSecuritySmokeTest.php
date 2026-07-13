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
foreach (['settings', 'saveSettings', 'saveOrganizationSettings'] as $method) {
    if (preg_match('/#\[NoAdminRequired\]\s+public function ' . $method . '\b/s', $source)) {
        throw new RuntimeException("Admin-Einstellung {$method} ist fuer normale Nutzer*innen freigegeben.");
    }
}
foreach (['preferences', 'savePreferences', 'saveShiftDefaults', 'meetingGaps'] as $method) {
    if (!preg_match('/#\[NoAdminRequired\]\s+public function ' . $method . '\b/s', $source)) {
        throw new RuntimeException("Angemeldeter API-Pfad {$method} fehlt.");
    }
}
if (preg_match('/#\[NoCSRFRequired\]\s+#\[NoAdminRequired\]\s+public function (savePreferences|saveShiftDefaults|meetingGaps)\b/s', $source)) {
    throw new RuntimeException('Neue schreibende API-Pfade umgehen den CSRF-Schutz.');
}
if (str_contains($source, 'Response::STATUS_') || !str_contains($source, 'Http::STATUS_BAD_REQUEST')) {
    throw new RuntimeException('Controller verwendet nicht den Nextcloud-HTTP-Statusvertrag.');
}

echo "ControllerSecuritySmokeTest: OK\n";
