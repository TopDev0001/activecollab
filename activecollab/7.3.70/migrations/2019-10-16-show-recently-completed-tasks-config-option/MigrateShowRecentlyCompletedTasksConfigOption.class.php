<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateShowRecentlyCompletedTasksConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('show_recently_completed_tasks', true);
    }
}
