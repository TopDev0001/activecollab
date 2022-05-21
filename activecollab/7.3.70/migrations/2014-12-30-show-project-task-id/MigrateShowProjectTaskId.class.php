<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce show project and task ID options.
 *
 * @package ActiveCollab.migrations
 */
class MigrateShowProjectTaskId extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('show_project_id', false);
        $this->addConfigOption('show_task_id', true);
    }
}
