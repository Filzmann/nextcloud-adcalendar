<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

/**
 * Zweck: Bildet die transitive organisatorische Weisungshierarchie fuer Kalenderbearbeitung ab.
 * Vertrag: Eine Leitungsrolle darf alle ihr direkt oder indirekt zugeordneten Zielrollen bearbeiten.
 */
final class CalendarHierarchyPolicy {
    public const GF_AS = 'ad-GF-AS';
    public const GF_DIGI = 'ad-GF-Digi';
    public const ASSISTANT_GF_DIGI = 'ad-AsdGF-Digi';
    public const FINANCE_LEAD = 'ad-Leitung-Finanzen-Lohn';
    public const FINANCE = 'ad-Finanzen-Lohn';
    public const IT = 'ad-IT';
    public const SECRETARIAT = 'ad-Sekretariat';
    public const PDL = 'ad-PDL';
    public const BL = 'ad-BL';
    public const DEPUT_BL = 'ad-StvBL';

    public function manages(array $actorGroups, array $targetGroups): bool {
        if (in_array(self::GF_AS, $actorGroups, true) && array_intersect($targetGroups, [
            self::PDL, self::BL, self::DEPUT_BL,
            CalendarAccessService::ROLE_PFK, CalendarAccessService::ROLE_OFFICE, CalendarAccessService::ROLE_EB,
            CalendarAccessService::ROLE_STAFF_HR, CalendarAccessService::ROLE_STAFF_QMB, self::SECRETARIAT,
        ]) !== []) return true;
        if (in_array(self::GF_DIGI, $actorGroups, true) && array_intersect($targetGroups, [
            self::ASSISTANT_GF_DIGI, self::FINANCE_LEAD, self::FINANCE, self::IT, self::SECRETARIAT,
        ]) !== []) return true;
        if (in_array(self::ASSISTANT_GF_DIGI, $actorGroups, true) && in_array(self::IT, $targetGroups, true)) return true;
        if (in_array(self::FINANCE_LEAD, $actorGroups, true) && in_array(self::FINANCE, $targetGroups, true)) return true;
        if (in_array(self::PDL, $actorGroups, true) && in_array(CalendarAccessService::ROLE_PFK, $targetGroups, true)) return true;
        return false;
    }

    public function targetIsSuperior(array $actorGroups, array $targetGroups): bool {
        return $this->structurallyManages($targetGroups, $actorGroups) && !$this->structurallyManages($actorGroups, $targetGroups);
    }

    private function structurallyManages(array $actorGroups, array $targetGroups): bool {
        if ($this->manages($actorGroups, $targetGroups)) return true;
        if (array_intersect([self::BL, self::DEPUT_BL], $actorGroups) !== []
            && array_intersect([CalendarAccessService::ROLE_OFFICE, CalendarAccessService::ROLE_EB], $targetGroups) !== []) return true;
        return false;
    }
}
