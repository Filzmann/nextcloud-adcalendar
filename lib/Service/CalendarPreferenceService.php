<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\AdCalendar\AppInfo\Application;
use OCP\IConfig;

/**
 * Zweck: Speichert den bewusst per „Zum Standard machen“ gesetzten persönlichen Kalenderfilter.
 * Vertrag: Unbekannte Personen, Rollen und Bereiche gelangen nicht in den gespeicherten Frontendzustand.
 */
final class CalendarPreferenceService {
    private const FILTER_KEY = 'filter_default';
    private const SHIFT_KEY = 'shift_defaults';

    public function __construct(private IConfig $config) {}

    public function filterDefault(string $uid, array $employees, array $roles, array $areas): ?array {
        $raw = $this->config->getUserValue($uid, Application::APP_ID, self::FILTER_KEY, '');
        if ($raw === '') return null;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $this->normalize($decoded, $employees, $roles, $areas) : null;
    }

    public function saveFilterDefault(string $uid, array $filters, array $employees, array $roles, array $areas): array {
        $normalized = $this->normalize($filters, $employees, $roles, $areas);
        $this->config->setUserValue($uid, Application::APP_ID, self::FILTER_KEY, json_encode($normalized, JSON_THROW_ON_ERROR));
        return $normalized;
    }

    public function shiftDefaults(string $uid): array {
        $raw = $this->config->getUserValue($uid, Application::APP_ID, self::SHIFT_KEY, '');
        $decoded = $raw === '' ? [] : json_decode($raw, true);
        return $this->normalizeShiftDefaults(is_array($decoded) ? $decoded : []);
    }

    public function saveShiftDefaults(string $uid, array $defaults): array {
        $normalized = $this->normalizeShiftDefaults($defaults);
        $this->config->setUserValue($uid, Application::APP_ID, self::SHIFT_KEY, json_encode($normalized, JSON_THROW_ON_ERROR));
        return $normalized;
    }

    private function normalize(array $filters, array $employees, array $roles, array $areas): array {
        return [
            'people' => $this->allowedList($filters['people'] ?? [], $employees),
            'roles' => $this->allowedList($filters['roles'] ?? [], $roles),
            'areas' => $this->allowedList($filters['areas'] ?? [], $areas),
            'vertical' => filter_var($filters['vertical'] ?? true, FILTER_VALIDATE_BOOL),
            'empty' => filter_var($filters['empty'] ?? false, FILTER_VALIDATE_BOOL),
            'showLeadershipStaff' => filter_var($filters['showLeadershipStaff'] ?? true, FILTER_VALIDATE_BOOL),
        ];
    }

    private function allowedList(mixed $values, array $allowed): array {
        if (!is_array($values)) return [];
        $allowedMap = array_fill_keys(array_map('strval', $allowed), true);
        return array_values(array_unique(array_filter(array_map('strval', $values), static fn(string $value): bool => isset($allowedMap[$value]))));
    }

    private function normalizeShiftDefaults(array $defaults): array {
        $result = [];
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $value = is_array($defaults[(string)$weekday] ?? $defaults[$weekday] ?? null) ? ($defaults[(string)$weekday] ?? $defaults[$weekday]) : [];
            $result[(string)$weekday] = [
                'enabled' => filter_var($value['enabled'] ?? true, FILTER_VALIDATE_BOOL),
                'start' => $this->time($value['start'] ?? '08:00', '08:00'),
                'end' => $this->time($value['end'] ?? '16:00', '16:00'),
            ];
        }
        return $result;
    }

    private function time(mixed $value, string $fallback): string {
        $value = (string)$value;
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1 ? $value : $fallback;
    }
}
