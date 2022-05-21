<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateFixTaskDependencyIdColumn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('task_dependencies')) {
            $task_dependencies = $this->useTableForAlter('task_dependencies');

            $this->execute(
                'ALTER TABLE ' . $task_dependencies->getName() . ' MODIFY COLUMN `id` INT AUTO_INCREMENT UNIQUE'
            );

            $this->doneUsingTables();
        }
    }
}
