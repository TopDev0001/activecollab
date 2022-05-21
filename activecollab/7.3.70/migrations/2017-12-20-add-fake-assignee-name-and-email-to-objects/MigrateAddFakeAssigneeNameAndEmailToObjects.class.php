<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddFakeAssigneeNameAndEmailToObjects extends AngieModelMigration
{
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');
        $recurring_tasks = $this->useTableForAlter('recurring_tasks');
        $subtasks = $this->useTableForAlter('subtasks');

        $this->addObjectColumns($tasks, 'created_from_recurring_task_id');
        $this->addObjectColumns($recurring_tasks, 'last_trigger_on');
        $this->addObjectColumns($subtasks, 'original_is_trashed');
    }

    private function addObjectColumns(DBTable $object, $after_column)
    {
        $columns = ['fake_assignee_email', 'fake_assignee_name'];

        foreach ($columns as $column) {
            if (!$object->getColumn($column)) {
                $object->addColumn(
                    DBStringColumn::create($column, 150),
                    $after_column
                );
            }
        }

        $this->doneUsingTables();
    }
}
