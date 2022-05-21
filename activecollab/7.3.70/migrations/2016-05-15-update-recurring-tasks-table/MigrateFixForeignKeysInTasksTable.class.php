<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateFixForeignKeysInTasksTable extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateFixCreatedFromRecurringTaskId');
    }

    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        foreach (['project_id', 'assignee_id', 'delegated_by_id'] as $field) {
            $this->execute("UPDATE tasks SET $field = ? WHERE $field IS NULL", 0);
        }

        $tasks->alterColumn('project_id', DBFkColumn::create('project_id', 0, true));
        $tasks->alterColumn('assignee_id', DBFkColumn::create('assignee_id', 0, true));
        $tasks->alterColumn('delegated_by_id', DBFkColumn::create('delegated_by_id', 0, true));
        $tasks->alterColumn('job_type_id', DBFkColumn::create('job_type_id')->setSize(DBColumn::SMALL));

        foreach (['project_id', 'assignee_id', 'delegated_by_id'] as $field) {
            if (!$tasks->getIndex($field)) {
                $tasks->addIndex(new DBIndex($field));
            }
        }
    }
}
