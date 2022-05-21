<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTimesheetEnabledAndEnabledLockConfigOption extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $this->addConfigOption('timesheet_enabled', true, true);
            $this->addConfigOption('timesheet_enabled_lock', false, true);
        }
    }
}
