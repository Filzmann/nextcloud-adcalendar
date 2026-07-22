<?php

declare(strict_types=1);

namespace OCP {
    interface IAppConfig { public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string; }
    interface IURLGenerator { public function linkToRouteAbsolute(string $routeName, array $arguments = []): string; }
}
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; }
    interface IClient { public function post(string $uri, array $options = []): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }
namespace OCA\AdCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore {
        public string $state = '';
        public bool $consumed = false;
        public array $saved = [];
        public function createOAuthState(string $uid): string { return $this->state = 'state-for-' . $uid; }
        public function consumeOAuthState(string $uid, string $state): bool { if ($this->consumed || $state !== $this->state) return false; $this->consumed = true; return true; }
        public function save(string $uid, string $provider, array $connection): void { $this->saved = [$uid, $provider, $connection]; }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/CalendarSync/GoogleOAuthService.php';

    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IAppConfig;
    use OCP\IURLGenerator;

    $config = new class implements IAppConfig {
        public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string { return ['google_oauth_client_id' => 'client-id', 'google_oauth_client_secret' => 'client-secret'][$key] ?? $default; }
    };
    $urls = new class implements IURLGenerator { public function linkToRouteAbsolute(string $routeName, array $arguments = []): string { return 'https://cloud.example.test/apps/adcalendar/oauth/google/callback'; } };
    $response = static fn(array $data): IResponse => new class($data) implements IResponse {
        public function __construct(private array $data) {}
        public function getBody(): string { return json_encode($this->data, JSON_THROW_ON_ERROR); }
        public function getStatusCode(): int { return 200; }
    };
    $client = new class([$response(['access_token' => 'access-a', 'refresh_token' => 'refresh-a', 'expires_in' => 3600]), $response(['access_token' => 'access-b', 'expires_in' => 3600])]) implements IClient {
        public array $calls = [];
        public function __construct(private array $responses) {}
        public function post(string $uri, array $options = []): IResponse { $this->calls[] = [$uri, $options]; return array_shift($this->responses); }
    };
    $clients = new class($client) implements IClientService { public function __construct(private IClient $client) {} public function newClient(): IClient { return $this->client; } };
    $store = new ExternalCalendarConnectionStore();
    $oauth = new GoogleOAuthService($config, $urls, $clients, $store);

    $authorizationUrl = $oauth->authorizationUrl('person-a');
    parse_str((string)parse_url($authorizationUrl, PHP_URL_QUERY), $query);
    if (($query['state'] ?? '') !== 'state-for-person-a' || ($query['access_type'] ?? '') !== 'offline' || !str_contains((string)($query['scope'] ?? ''), 'calendar.app.created') || str_contains($authorizationUrl, 'client-secret')) {
        throw new RuntimeException('Google-Autorisierungs-URL ist nicht minimal, offlinefähig oder geheimnisarm.');
    }
    $tokens = $oauth->exchange('person-a', 'state-for-person-a', 'authorization-code');
    if (($tokens['refreshToken'] ?? '') !== 'refresh-a' || ($tokens['accessToken'] ?? '') !== 'access-a') throw new RuntimeException('Google-Code wurde nicht in Offline-Tokens getauscht.');
    try { $oauth->exchange('person-a', 'state-for-person-a', 'replay-code'); throw new RuntimeException('OAuth-Replay wurde akzeptiert.'); } catch (InvalidArgumentException) {}

    $store->consumed = false;
    $token = $oauth->accessToken('person-a', ['refreshToken' => 'refresh-a', 'accessToken' => 'expired', 'expiresAt' => 1]);
    if ($token !== 'access-b' || ($store->saved[1] ?? '') !== 'google' || ($store->saved[2]['refreshToken'] ?? '') !== 'refresh-a') throw new RuntimeException('Google-Refresh-Token wird nicht sicher erneuert und bewahrt.');
    $requests = json_encode($client->calls, JSON_THROW_ON_ERROR);
    if (!str_contains($requests, 'authorization_code') || !str_contains($requests, 'refresh_token')) throw new RuntimeException('Google-Tokenflows sind unvollständig.');

    echo "GoogleOAuthServiceTest: OK\n";
}
