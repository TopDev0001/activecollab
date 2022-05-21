<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIdAndCreatedOnForTaskDependency extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('task_dependencies')) {
            $task_dependencies = $this->useTableForAlter('task_dependencies');

            if (!$task_dependencies->getColumn('created_on')) {
                $task_dependencies->addColumn(new DBCreatedOnColumn(), 'child_id');
                $this->execute('UPDATE ' . $task_dependencies->getName() . ' SET created_on = NOW()');
            }

            if (!$task_dependencies->getColumn('id')) {
                $this->execute(
                    'ALTER TABLE ' . $task_dependencies->getName() . ' ADD COLUMN `id` INT AUTO_INCREMENT UNIQUE FIRST'
                );
            } else {
                $this->execute(
                    'ALTER TABLE ' . $task_dependencies->getName() . ' MODIFY COLUMN `id` INT AUTO_INCREMENT UNIQUE'
                );
            }

            $this->doneUsingTables();
        }
    }
}
