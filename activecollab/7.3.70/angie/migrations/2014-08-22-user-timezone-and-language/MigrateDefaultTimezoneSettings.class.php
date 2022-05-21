<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate default time zone settings.
 *
 * @package angie.migrations
 */
class MigrateDefaultTimezoneSettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->setConfigOptionValue('time_timezone', 'UTC', true);
        $this->removeConfigOption('time_dst');
    }
}
