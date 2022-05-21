<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add config options for page display modes on project tasks page.
 *
 * @package angie.migrations
 */
class MigrateTasksDisplayModeConfigOptions extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $this->addConfigOption('display_mode_project_tasks', 'list');
    }
}
