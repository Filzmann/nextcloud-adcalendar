<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use DateTimeImmutable;
use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\Service\CalendarAccessService;
use OCA\AdCalendar\Service\CalendarService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

final class ApiController extends Controller {
    public function __construct(IRequest $request, private CalendarAccessService $access, private CalendarService $calendar) { parent::__construct(Application::APP_ID, $request); }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function week(string $start): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $date = new DateTimeImmutable($start);
            return new JSONResponse($this->calendar->week($date, $this->access->visibleEmployees()));
        } catch (\Throwable) { return new JSONResponse(['error' => 'Ungueltiger Wochenbeginn.'], Response::STATUS_BAD_REQUEST); }
    }

    #[NoAdminRequired]
    public function create(string $employeeUid, string $start, string $end, string $type, string $title = ''): JSONResponse {
        return $this->save(null, compact('employeeUid', 'start', 'end', 'type', 'title'));
    }

    #[NoAdminRequired]
    public function update(int $id, string $employeeUid, string $start, string $end, string $type, string $title = ''): JSONResponse {
        $existing = $this->calendar->existing($id);
        if (!$this->access->canManage($existing->employeeUid()) || !$this->access->canManage($employeeUid)) return $this->denied();
        return $this->save($id, compact('employeeUid', 'start', 'end', 'type', 'title'));
    }

    #[NoAdminRequired]
    public function delete(int $id): JSONResponse {
        try { $entry = $this->calendar->existing($id); } catch (\Throwable) { return new JSONResponse(['error' => 'Nicht gefunden.'], Response::STATUS_NOT_FOUND); }
        if (!$this->access->canManage($entry->employeeUid())) return $this->denied();
        $this->calendar->delete($id);
        return new JSONResponse(['deleted' => true]);
    }

    private function save(?int $id, array $payload): JSONResponse {
        if (!$this->access->canManage($payload['employeeUid'])) return $this->denied();
        try {
            $user = $this->access->currentUser();
            return new JSONResponse(['id' => $this->calendar->save($payload, $id, $user?->getUID() ?? '')]);
        } catch (\Throwable) { return new JSONResponse(['error' => 'Der Kalendereintrag ist ungueltig.'], Response::STATUS_BAD_REQUEST); }
    }

    private function denied(): JSONResponse { return new JSONResponse(['error' => 'Keine Berechtigung.'], Response::STATUS_FORBIDDEN); }
}
