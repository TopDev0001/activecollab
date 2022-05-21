<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddUserDailyCapacityConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('user_daily_capacity', 8);
    }
}
