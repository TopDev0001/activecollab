<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateOverrideTaskFilterStatus extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('tasks_filter_status', 'open');
    }
}
