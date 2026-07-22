<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IUser { public function getUID(): string; }
    interface IURLGenerator { public function linkToRoute(string $routeName, array $arguments = []): string; }
}
namespace OCP\AppFramework {
    class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }
    class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; }
}
namespace OCP\AppFramework\Http {
    class JSONResponse { public function __construct(private array $data = [], private int $status = 200) {} public function getData(): array { return $this->data; } public function getStatus(): int { return $this->status; } }
    class RedirectResponse { public function __construct(private string $url) {} public function getRedirectURL(): string { return $this->url; } }
}
namespace Psr\Log { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }
namespace OCA\AdCalendar\Service {
    final class CalendarAccessService { public ?\OCP\IUser $user = null; public function currentUser(): ?\OCP\IUser { return $this->user; } }
    final class ExternalCalendarService {
        public array $calls = [];
        public function status(string $uid): array { $this->calls[] = ['status', $uid]; return ['kopano' => ['connected' => false]]; }
        public function connectCalDav(string $uid, string $provider, string $serverUrl, string $username, string $password): array { $this->calls[] = ['connect', $uid, $provider]; return ['kopano' => ['connected' => true]]; }
        public function disconnect(string $uid, string $provider): array { $this->calls[] = ['disconnect', $uid, $provider]; return ['kopano' => ['connected' => false]]; }
        public function connectGoogle(string $uid, array $tokens): array { $this->calls[] = ['google', $uid]; return ['google' => ['connected' => true]]; }
    }
}
namespace OCA\AdCalendar\CalendarSync {
    final class GoogleOAuthService {
        public array $calls = [];
        public function authorizationUrl(string $uid): string { $this->calls[] = ['start', $uid]; return 'https://accounts.google.test/oauth'; }
        public function exchange(string $uid, string $state, string $code): array { $this->calls[] = ['exchange', $uid, $state]; return ['refreshToken' => 'internal']; }
        public function cancel(string $uid, string $state): void { $this->calls[] = ['cancel', $uid, $state]; }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Controller/ExternalCalendarController.php';

    use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
    use OCA\AdCalendar\Controller\ExternalCalendarController;
    use OCA\AdCalendar\Service\CalendarAccessService;
    use OCA\AdCalendar\Service\ExternalCalendarService;
    use OCP\IRequest;
    use OCP\IURLGenerator;
    use OCP\IUser;
    use Psr\Log\LoggerInterface;

    $access = new CalendarAccessService();
    $calendars = new ExternalCalendarService();
    $google = new GoogleOAuthService();
    $controller = new ExternalCalendarController(
        new class implements IRequest {},
        $access,
        $calendars,
        $google,
        new class implements IURLGenerator { public function linkToRoute(string $routeName, array $arguments = []): string { return '/apps/adcalendar/'; } },
        new class implements LoggerInterface { public function error(string|\Stringable $message, array $context = []): void {} },
    );

    if ($controller->connectCalDav('kopano', 'https://mail.adberlin.org', 'person-a', 'secret')->getStatus() !== 403 || $calendars->calls !== []) {
        throw new RuntimeException('Nicht angemeldete Person kann eine Providerverbindung verändern.');
    }
    $access->user = new class implements IUser { public function getUID(): string { return 'person-a'; } };
    $connected = $controller->connectCalDav('kopano', 'https://mail.adberlin.org', 'person-a', 'secret');
    if ($connected->getStatus() !== 200 || $calendars->calls !== [['connect', 'person-a', 'kopano']] || str_contains(json_encode($connected->getData()), 'secret')) {
        throw new RuntimeException('Providerverbindung ist nicht auf das angemeldete Konto begrenzt oder gibt Geheimnisse aus.');
    }
    if ($controller->googleStart()->getData()['authorizationUrl'] !== 'https://accounts.google.test/oauth' || $google->calls !== [['start', 'person-a']]) {
        throw new RuntimeException('Google-Autorisierung ist nicht auf das angemeldete Konto begrenzt.');
    }
    $redirect = $controller->googleCallback('state-a', 'code-a');
    if (!str_contains($redirect->getRedirectURL(), 'google-connected') || $google->calls[1] !== ['exchange', 'person-a', 'state-a'] || $calendars->calls[1] !== ['google', 'person-a']) {
        throw new RuntimeException('Google-Callback bindet Status und Verbindung nicht an die aktuelle Sitzung.');
    }

    echo "ExternalCalendarControllerExecutionTest: OK\n";
}
