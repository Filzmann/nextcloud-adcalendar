<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Verknüpft die pro Person gespeicherten Termine eines gemeinsamen Meetings. */
final class Version000005Date202607130002 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table = $schema->getTable('adc_entries');
        $changed = false;

        if (!$table->hasColumn('meeting_uid')) {
            $table->addColumn('meeting_uid', Types::STRING, ['length' => 64, 'notnull' => false]);
            $changed = true;
        }
        if (!$table->hasIndex('adc_meeting_uid')) {
            $table->addIndex(['meeting_uid'], 'adc_meeting_uid');
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
