<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use DateTimeImmutable;
use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\Service\CalendarAccessService;
use OCA\AdCalendar\Service\CalendarService;
use OCA\AdCalendar\Service\CalendarSettingsService;
use OCA\AdCalendar\Service\CalendarPreferenceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class ApiController extends Controller {
    public function __construct(
        IRequest $request,
        private CalendarAccessService $access,
        private CalendarService $calendar,
        private CalendarSettingsService $settingsService,
        private CalendarPreferenceService $preferences,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function week(string $start): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $date = new DateTimeImmutable($start);
            $employees = $this->access->visibleEmployees();
            $response = $this->calendar->week($date, $employees);
            $response['currentUserProfile'] = $this->access->currentProfile();
            $response['defaultFilters'] = $this->preferencesFor($employees);
            $response['shiftDefaults'] = $this->preferences->shiftDefaults($this->access->currentUser()?->getUID() ?? '');
            return new JSONResponse($response);
        } catch (\Throwable $error) {
            $this->logger->error('Wochenansicht konnte nicht aufgebaut werden.', ['exception' => $error]);
            return new JSONResponse(['error' => 'Die Wochenansicht konnte nicht geladen werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function create(string $employeeUid, string $start, string $end, string $type, string $title = ''): JSONResponse {
        return $this->save(null, compact('employeeUid', 'start', 'end', 'type', 'title'));
    }

    #[NoAdminRequired]
    public function update(int $id, string $employeeUid, string $start, string $end, string $type, string $title = ''): JSONResponse {
        $existing = $this->calendar->existing($id);
        if (!$this->access->canManage($existing->employeeUid()) || !$this->access->canManage($employeeUid)) {
            return $this->denied();
        }
        return $this->save($id, compact('employeeUid', 'start', 'end', 'type', 'title'));
    }

    #[NoAdminRequired]
    public function delete(int $id, string $childMode = ''): JSONResponse {
        try {
            $entry = $this->calendar->existing($id);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Nicht gefunden.'], Http::STATUS_NOT_FOUND);
        }
        if (!$this->access->canManage($entry->employeeUid())) return $this->denied();
        try {
            $preview = $this->calendar->deletionPreview($id);
            if ($entry->type() === 'shift' && $preview['children'] !== [] && $childMode === '') {
                return new JSONResponse(['confirmationRequired' => true, 'children' => $preview['children']], Http::STATUS_CONFLICT);
            }
            $this->calendar->delete($id, $childMode);
            return new JSONResponse(['deleted' => true, 'childMode' => $childMode]);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Der Eintrag konnte nicht geloescht werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    public function settings(): JSONResponse {
        return new JSONResponse(['peerEditing' => $this->settingsService->peerEditing()]);
    }

    public function saveSettings(array $peerEditing): JSONResponse {
        return new JSONResponse(['peerEditing' => $this->settingsService->savePeerEditing($peerEditing)]);
    }

    #[NoAdminRequired]
    public function preferences(): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        $user = $this->access->currentUser();
        return new JSONResponse(['filters' => $this->preferencesFor($this->access->visibleEmployees()), 'shiftDefaults' => $this->preferences->shiftDefaults($user?->getUID() ?? '')]);
    }

    #[NoAdminRequired]
    public function savePreferences(array $filters): JSONResponse {
        $user = $this->access->currentUser();
        if ($user === null) return $this->denied();
        $employees = $this->access->visibleEmployees();
        [$uids, $roles, $areas] = $this->filterOptions($employees);
        return new JSONResponse(['filters' => $this->preferences->saveFilterDefault($user->getUID(), $filters, $uids, $roles, $areas)]);
    }

    #[NoAdminRequired]
    public function saveShiftDefaults(array $shiftDefaults): JSONResponse {
        $user = $this->access->currentUser();
        if ($user === null) return $this->denied();
        return new JSONResponse(['shiftDefaults' => $this->preferences->saveShiftDefaults($user->getUID(), $shiftDefaults)]);
    }

    #[NoAdminRequired]
    public function meetingGaps(string $start, array $employeeUids, int $durationMinutes = 60): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $uids = array_values(array_unique(array_map('strval', $employeeUids)));
            $visible = array_fill_keys(array_column($this->access->visibleEmployees(), 'uid'), true);
            if (!$this->validMeetingRequest($uids, $durationMinutes, $visible)) {
                throw new \InvalidArgumentException();
            }
            return new JSONResponse(['gaps' => $this->calendar->meetingGaps(new DateTimeImmutable($start), $uids, $durationMinutes)]);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Teilnehmende, Kalenderwoche oder Dauer sind ungueltig.'], Http::STATUS_BAD_REQUEST);
        }
    }

    private function save(?int $id, array $payload): JSONResponse {
        if (!$this->access->canManage($payload['employeeUid'])) {
            return $this->denied();
        }
        try {
            $user = $this->access->currentUser();
            return new JSONResponse(['id' => $this->calendar->save($payload, $id, $user?->getUID() ?? '')]);
        } catch (\Throwable) {
            return new JSONResponse(['error' => 'Der Kalendereintrag ist ungueltig.'], Http::STATUS_BAD_REQUEST);
        }
    }

    private function denied(): JSONResponse {
        return new JSONResponse(['error' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
    }

    private function preferencesFor(array $employees): ?array {
        $user = $this->access->currentUser();
        if ($user === null) return null;
        [$uids, $roles, $areas] = $this->filterOptions($employees);
        return $this->preferences->filterDefault($user->getUID(), $uids, $roles, $areas);
    }

    private function filterOptions(array $employees): array {
        return [
            array_values(array_unique(array_column($employees, 'uid'))),
            array_values(array_unique(array_merge(...array_map(static fn(array $employee): array => $employee['roles'], $employees)))),
            array_values(array_unique(array_merge(...array_map(static fn(array $employee): array => $employee['areas'], $employees)))),
        ];
    }

    private function validMeetingRequest(array $uids, int $durationMinutes, array $visible): bool {
        if (count($uids) < 2 || count($uids) > 20 || $durationMinutes < 15 || $durationMinutes > 480) {
            return false;
        }
        foreach ($uids as $uid) {
            if (!isset($visible[$uid])) return false;
        }
        return true;
    }
}
