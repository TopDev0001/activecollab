<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate scheduled task timestamps to Heartbeat timestamps.
 *
 * @package angie.migrations
 */
class MigrateScheduledTaskTimestamps extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('last_hourly_activity');

        $this->renameConfigOption('last_frequently_activity', 'heartbeat_last_pulse');
        $this->renameConfigOption('last_daily_activity', 'heartbeat_last_maintenance');

        $this->addConfigOption('heartbeat_last_morning_mail');

        $this->setConfigOptionValue('heartbeat_last_pulse', 0);
        $this->setConfigOptionValue('heartbeat_last_maintenance', 0);
    }
}
