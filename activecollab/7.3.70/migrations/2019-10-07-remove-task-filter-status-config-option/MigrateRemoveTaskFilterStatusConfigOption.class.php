<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRemoveTaskFilterStatusConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->removeConfigOption('tasks_filter_status');
    }
}
