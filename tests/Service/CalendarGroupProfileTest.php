<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../localbase/lib/Organization/AdOrganizationDefinition.php';
require_once __DIR__ . '/../../../localbase/lib/Organization/AdOrganizationHierarchy.php';
require_once __DIR__ . '/../../lib/Service/CalendarAccessService.php';
require_once __DIR__ . '/../../lib/Service/CalendarHierarchyPolicy.php';
require_once __DIR__ . '/../../lib/Service/CalendarGroupProfile.php';

use OCA\AdCalendar\Service\CalendarGroupProfile;

$profiles = new CalendarGroupProfile();
$pfk = $profiles->get(['ad-PFK', 'ad-Bereich-Sued']);
if ($pfk['areas'] !== [] || $pfk['clusters'] !== ['ad-PFK']) throw new RuntimeException('PFK darf keinem Buerobereich zugeordnet werden.');
$staff = $profiles->get(['ad-Stab-HR', 'ad-Bereich-West']);
if ($staff['areas'] !== [] || $staff['clusters'] !== ['ad-Stab-HR']) throw new RuntimeException('Stabsstellen duerfen keinem Buerobereich zugeordnet werden.');
$office = $profiles->get(['ad-Buero', 'ad-Bereich-Nordost']);
if ($office['areas'] !== ['ad-Bereich-Nordost'] || $office['clusters'] !== ['ad-Buero#ad-Bereich-Nordost']) throw new RuntimeException('BO-Bereich wurde nicht dynamisch kombiniert.');
$eb = $profiles->get(['ad-EB', 'ad-Bereich-West']);
if ($eb['areas'] !== ['ad-Bereich-West']) throw new RuntimeException('EB-Bereich wurde nicht uebernommen.');
$blNow = $profiles->get(['ad-BL', 'ad-Bereich-Nordost', 'ad-Bereich-West']);
if ($blNow['roles'] !== ['ad-BL'] || $blNow['clusters'] !== ['ad-BL#ad-Bereich-Nordost', 'ad-BL#ad-Bereich-West']) throw new RuntimeException('BL NOW muss dynamisch in BL-NO und BL-W gefunden werden.');

echo "CalendarGroupProfileTest: OK\n";
