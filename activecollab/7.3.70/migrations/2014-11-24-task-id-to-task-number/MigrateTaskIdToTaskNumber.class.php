<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate task ID to task number.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskIdToTaskNumber extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        foreach ($tasks->getIndices() as $index) {
            $columns = $index->getColumns();

            if (count($columns) === 2 && in_array('project_id', $columns) && in_array('task_id', $columns)) {
                $tasks->dropIndex($index->getName());
                break;
            }
        }

        $tasks->alterColumn('task_id', DBIntegerColumn::create('task_number', 10, 0)->setUnsigned(true));
        $tasks->addIndex(DBIndex::create('project_task_number', DBIndex::UNIQUE, ['project_id', 'task_number']));

        $this->doneUsingTables();
    }
}
