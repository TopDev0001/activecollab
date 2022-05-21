<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Task options administration.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage migrations
 */
class MigrateNewTaskOptionsConfigValue extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('task_options', []);
    }
}
