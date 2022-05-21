<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddSkipDaysOffWhenReschedulingConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('skip_days_off_when_rescheduling', true);
    }
}
