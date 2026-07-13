<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\LocalBase\Organization\AdOrganizationPermissionPolicy;

/** Zweck: Bewertet die fachliche Schreibmatrix ohne Nextcloud-Infrastruktur. */
final class CalendarPermissionPolicy extends AdOrganizationPermissionPolicy {
    public function __construct(CalendarHierarchyPolicy $hierarchy) { parent::__construct($hierarchy); }
}
