<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../../lib/Controller/ExternalCalendarController.php');
$routes = file_get_contents(__DIR__ . '/../../appinfo/routes.php');
$store = file_get_contents(__DIR__ . '/../../lib/CalendarSync/ExternalCalendarConnectionStore.php');
$oauth = file_get_contents(__DIR__ . '/../../lib/CalendarSync/GoogleOAuthService.php');
if ($source === false || $routes === false || $store === false || $oauth === false) throw new RuntimeException('Externer Kalendervertrag konnte nicht gelesen werden.');

foreach (['status', 'connectCalDav', 'disconnect', 'googleStart', 'googleCallback'] as $method) {
    if (!preg_match('/#\[NoAdminRequired\][\s\S]{0,120}public function ' . $method . '\b/', $source)) throw new RuntimeException("Persönlicher Providerpfad fehlt: {$method}");
}
foreach (['connectCalDav', 'disconnect', 'googleStart'] as $method) {
    if (preg_match('/#\[NoCSRFRequired\][\s\S]{0,120}public function ' . $method . '\b/', $source)) throw new RuntimeException("Schreibender Providerpfad umgeht CSRF: {$method}");
}
if (!preg_match('/#\[NoAdminRequired\]\s+#\[NoCSRFRequired\]\s+public function googleCallback\b/', $source)
    || !str_contains($source, 'googleOAuth->exchange($uid, $state, $code)')) {
    throw new RuntimeException('Google-Callback ist nicht ausschließlich durch den einmaligen OAuth-Status geschützt.');
}
foreach (["'external_calendar#status'", "'external_calendar#connectCalDav'", "'external_calendar#disconnect'", "'external_calendar#googleStart'", "'external_calendar#googleCallback'"] as $route) {
    if (!str_contains($routes, $route)) throw new RuntimeException("Providerroute fehlt: {$route}");
}
foreach (['ICrypto', 'IUserConfig::FLAG_SENSITIVE', 'consumeOAuthState', 'hash_equals', 'deleteUserConfig'] as $contract) {
    if (!str_contains($store, $contract)) throw new RuntimeException("Geheimnis-/OAuth-Schutz fehlt: {$contract}");
}
foreach (['access_type', "'offline'", 'calendar.app.created', 'linkToRouteAbsolute', 'client_secret'] as $contract) {
    if (!str_contains($oauth, $contract)) throw new RuntimeException("Google-OAuth-Vertrag fehlt: {$contract}");
}
if (str_contains($source, "['password'") || str_contains($source, "['refreshToken'")) throw new RuntimeException('Controller gibt Kalendergeheimnisse direkt aus.');

echo "ExternalCalendarControllerSecurityTest: OK\n";
