<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateEditTaskFilterStatusForOccupiedAccounts extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('tasks_filter_status', 'all');
    }
}
