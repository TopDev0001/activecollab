<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add default task list name config option.
 *
 * @package ActiveCollab.migrations
 */
class MigrateAddDefaultTaskListNameConfigOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('default_task_list_name', 'Task List');
    }
}
