<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Repository;

use DateTimeImmutable;
use DateTimeZone;
use OCA\AdCalendar\Model\CalendarEntry;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** Zweck: Kapselt alle gebundenen Datenbankzugriffe auf Kalendereintraege. */
final class CalendarEntryRepository {
    public function __construct(private IDBConnection $db) {}

    /** @return list<CalendarEntry> */
    public function findRange(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids): array {
        if ($employeeUids === []) return [];
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'employee_uid', 'start_at', 'end_at', 'entry_type', 'title', 'parent_entry_id')->from('adc_entries')
            ->where($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->gt('end_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->in('employee_uid', $qb->createNamedParameter($employeeUids, IQueryBuilder::PARAM_STR_ARRAY)))
            ->orderBy('start_at', 'ASC');
        $rows = $qb->executeQuery()->fetchAllAssociative();
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $rows));
    }

    public function find(int $id): ?CalendarEntry {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'employee_uid', 'start_at', 'end_at', 'entry_type', 'title', 'parent_entry_id')->from('adc_entries')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        $row = $qb->executeQuery()->fetchAssociative();
        return $row === false ? null : CalendarEntry::get($this->mapRow($row));
    }

    public function save(CalendarEntry $entry, string $actorUid): int {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $values = [
            'employee_uid' => $entry->employeeUid(), 'start_at' => $entry->start(), 'end_at' => $entry->end(),
            'entry_type' => $entry->type(), 'title' => $entry->title(), 'parent_entry_id' => $entry->parentEntryId(), 'updated_at' => $now,
        ];
        $types = ['employee_uid' => IQueryBuilder::PARAM_STR, 'start_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE, 'end_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE, 'entry_type' => IQueryBuilder::PARAM_STR, 'title' => IQueryBuilder::PARAM_STR, 'parent_entry_id' => IQueryBuilder::PARAM_INT, 'updated_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE];
        $qb = $this->db->getQueryBuilder();
        if ($entry->id() === null) {
            $values += ['created_by_uid' => $actorUid, 'created_at' => $now];
            $types += ['created_by_uid' => IQueryBuilder::PARAM_STR, 'created_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE];
            $qb->insert('adc_entries');
        } else {
            $qb->update('adc_entries')->where($qb->expr()->eq('id', $qb->createNamedParameter($entry->id(), IQueryBuilder::PARAM_INT)));
        }
        foreach ($values as $field => $value) $qb->setValue($field, $qb->createNamedParameter($value, $value === null ? IQueryBuilder::PARAM_NULL : $types[$field]));
        $qb->executeStatement();
        return $entry->id() ?? (int)$this->db->lastInsertId('adc_entries');
    }

    public function delete(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('adc_entries')->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    /** @return list<CalendarEntry> */
    public function children(int $parentId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'employee_uid', 'start_at', 'end_at', 'entry_type', 'title', 'parent_entry_id')->from('adc_entries')
            ->where($qb->expr()->eq('parent_entry_id', $qb->createNamedParameter($parentId, IQueryBuilder::PARAM_INT)))
            ->orderBy('start_at', 'ASC');
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    public function detachChildren(int $parentId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('adc_entries')->set('parent_entry_id', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
            ->where($qb->expr()->eq('parent_entry_id', $qb->createNamedParameter($parentId, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    public function detachChild(int $childId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('adc_entries')->set('parent_entry_id', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($childId, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    public function deleteChildren(int $parentId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('adc_entries')->where($qb->expr()->eq('parent_entry_id', $qb->createNamedParameter($parentId, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    /**
     * Vertrag: Kindbehandlung und Dienstloeschung sind atomar; ein Fehler laesst alles unveraendert.
     */
    public function deleteShift(int $shiftId, string $childMode): void {
        $this->db->beginTransaction();
        try {
            if ($childMode === 'delete') $this->deleteChildren($shiftId);
            else $this->detachChildren($shiftId);
            $this->delete($shiftId);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /** @return list<CalendarEntry> */
    public function containingShifts(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end, ?int $excludeId = null): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'employee_uid', 'start_at', 'end_at', 'entry_type', 'title', 'parent_entry_id')->from('adc_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('entry_type', $qb->createNamedParameter(CalendarEntry::TYPE_SHIFT)))
            ->andWhere($qb->expr()->lte('start_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->gte('end_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
        if ($excludeId !== null) $qb->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId, IQueryBuilder::PARAM_INT)));
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    /** @return list<CalendarEntry> */
    public function overlappingShifts(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end, ?int $excludeId = null): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'employee_uid', 'start_at', 'end_at', 'entry_type', 'title', 'parent_entry_id')->from('adc_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('entry_type', $qb->createNamedParameter(CalendarEntry::TYPE_SHIFT)))
            ->andWhere($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->gt('end_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
        if ($excludeId !== null) $qb->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId, IQueryBuilder::PARAM_INT)));
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    public function existsCreatedBy(string $actorUid, DateTimeImmutable $start, DateTimeImmutable $end): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('adc_entries')
            ->where($qb->expr()->eq('created_by_uid', $qb->createNamedParameter($actorUid)))
            ->andWhere($qb->expr()->gte('start_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->setMaxResults(1);
        return $qb->executeQuery()->fetchOne() !== false;
    }

    public function existsCreatedByForEmployee(string $actorUid, string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('adc_entries')
            ->where($qb->expr()->eq('created_by_uid', $qb->createNamedParameter($actorUid)))
            ->andWhere($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->gte('start_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->setMaxResults(1);
        return $qb->executeQuery()->fetchOne() !== false;
    }

    private function mapRow(array $row): array {
        return ['id' => (int)$row['id'], 'employeeUid' => $row['employee_uid'], 'start' => (string)$row['start_at'], 'end' => (string)$row['end_at'], 'type' => $row['entry_type'], 'title' => $row['title'], 'parentEntryId' => $row['parent_entry_id'] === null ? null : (int)$row['parent_entry_id']];
    }
}
