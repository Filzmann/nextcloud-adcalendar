<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\LocalBase\Organization\AdOrganizationHierarchy;

/**
 * Zweck: Bildet die transitive organisatorische Weisungshierarchie für Kalenderbearbeitung ab.
 * Vertrag: Eine Leitungsrolle darf alle ihr direkt oder indirekt zugeordneten Zielrollen bearbeiten.
 */
final class CalendarHierarchyPolicy extends AdOrganizationHierarchy {}
