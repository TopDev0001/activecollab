<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateFixCreatedFromRecurringTaskId extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateUpdateRecurringTasksTable');
    }

    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        if (!$tasks->getColumn('created_from_recurring_task_id')) {
            $tasks->addColumn(DBFkColumn::create('created_from_recurring_task_id', 0, true), 'delegated_by_id');
        }

        if (!$tasks->getIndex('created_from_recurring_task_id')) {
            $tasks->addIndex(new DBIndex('created_from_recurring_task_id'));
        }
    }
}
