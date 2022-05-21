<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateDropExpensesState extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateTasksToNewStorage');
    }

    public function up()
    {
        $expenses = $this->useTableForAlter('expenses');

        $expenses->addColumn(
            new DBBoolColumn('is_trashed'),
            'position'
        );
        $expenses->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $expenses->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $expenses->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $expenses->addIndex(DBIndex::create('trashed_by_id'));

        // ---------------------------------------------------
        //  State
        // ---------------------------------------------------

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $expenses->getName() . ' SET is_trashed = ?, original_is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, false, STATE_TRASHED);

        if ($expenses->getColumn('state')) {
            $expenses->dropColumn('state');
        }

        if ($expenses->getColumn('original_state')) {
            $expenses->dropColumn('original_state');
        }

        $this->doneUsingTables();
    }
}
