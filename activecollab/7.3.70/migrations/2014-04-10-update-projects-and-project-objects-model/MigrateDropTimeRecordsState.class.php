<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateDropTimeRecordsState extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateTasksToNewStorage');
    }

    public function up()
    {
        $time_records = $this->useTableForAlter('time_records');

        $time_records->addColumn(
            new DBBoolColumn('is_trashed'),
            'position'
        );
        $time_records->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $time_records->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $time_records->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $time_records->addIndex(DBIndex::create('trashed_by_id'));

        // ---------------------------------------------------
        //  State
        // ---------------------------------------------------

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $time_records->getName() . ' SET is_trashed = ?, original_is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, false, STATE_TRASHED);

        $time_records->dropColumn('state');
        $time_records->dropColumn('original_state');
    }
}
