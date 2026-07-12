<?php

declare(strict_types=1);

if (!interface_exists('OCP\\IConfig')) {
    eval('namespace OCP; interface IConfig { public function getUserValue($userId, $appName, $key, $default = ""); public function setUserValue($userId, $appName, $key, $value, $preCondition = null); }');
}
if (!class_exists('OCA\\AdCalendar\\AppInfo\\Application')) {
    eval('namespace OCA\\AdCalendar\\AppInfo; final class Application { public const APP_ID = "adcalendar"; }');
}
require_once __DIR__ . '/../../lib/Service/CalendarPreferenceService.php';

use OCA\AdCalendar\Service\CalendarPreferenceService;
use OCP\IConfig;

$config = new class implements IConfig {
    public array $values = [];
    public function getUserValue($userId, $appName, $key, $default = '') { return $this->values[$userId][$appName][$key] ?? $default; }
    public function setUserValue($userId, $appName, $key, $value, $preCondition = null) { $this->values[$userId][$appName][$key] = $value; return true; }
};
$service = new CalendarPreferenceService($config);
if ($service->filterDefault('demo', ['a'], ['ad-Buero'], ['ad-Bereich-Sued']) !== null) throw new RuntimeException('Fehlender persoenlicher Standard muss null bleiben.');
$saved = $service->saveFilterDefault('demo', [
    'people' => ['a', 'fremd'], 'roles' => ['ad-Buero', 'ad-Unbekannt'],
    'areas' => ['ad-Bereich-Sued', 'ad-Bereich-Fremd'], 'vertical' => false, 'empty' => false, 'showLeadershipStaff' => false,
], ['a'], ['ad-Buero'], ['ad-Bereich-Sued']);
if ($saved !== ['people' => ['a'], 'roles' => ['ad-Buero'], 'areas' => ['ad-Bereich-Sued'], 'vertical' => false, 'empty' => false, 'showLeadershipStaff' => false]) {
    throw new RuntimeException('Persoenlicher Filterstandard wurde nicht auf erlaubte Werte begrenzt.');
}
if ($service->filterDefault('demo', ['a'], ['ad-Buero'], ['ad-Bereich-Sued']) !== $saved) throw new RuntimeException('Gespeicherter Filterstandard ist nicht lesbar.');
$shiftDefaults = $service->saveShiftDefaults('demo', [
    '1' => ['enabled' => true, 'start' => '07:30', 'end' => '15:45'],
    '2' => ['enabled' => false, 'start' => 'ungueltig', 'end' => '23:30'],
]);
if ($shiftDefaults['1'] !== ['enabled' => true, 'start' => '07:30', 'end' => '15:45']) throw new RuntimeException('Persoenliche Dienstzeit wurde nicht gespeichert.');
if ($shiftDefaults['2'] !== ['enabled' => false, 'start' => '08:00', 'end' => '23:30']) throw new RuntimeException('Dienstzeitnormalisierung ist falsch.');
if ($shiftDefaults['7'] !== ['enabled' => true, 'start' => '08:00', 'end' => '16:00']) throw new RuntimeException('Fehlender Wochentag hat keinen stabilen Standard.');
if ($service->shiftDefaults('demo') !== $shiftDefaults) throw new RuntimeException('Gespeicherte Dienstzeiten sind nicht lesbar.');

echo "CalendarPreferenceServiceTest: OK\n";
