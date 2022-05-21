<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateRecurringTasksTable extends AngieModelMigration
{
    public function up()
    {
        $recurring_tasks = $this->useTableForAlter('recurring_tasks');

        $recurring_tasks->alterColumn('project_id', DBFkColumn::create('project_id', 0, true));
        $recurring_tasks->alterColumn('assignee_id', DBFkColumn::create('assignee_id', 0, true));
        $recurring_tasks->alterColumn('delegated_by_id', DBFkColumn::create('delegated_by_id', 0, true));
        $recurring_tasks->alterColumn('job_type_id', DBFkColumn::create('job_type_id')->setSize(DBColumn::SMALL));

        $recurring_tasks->alterColumn('start_in', DBIntegerColumn::create('start_in')->setUnsigned(true));
        $recurring_tasks->alterColumn('due_in', DBIntegerColumn::create('due_in')->setUnsigned(true));
    }
}
