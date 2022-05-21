<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate control tower settings.
 *
 * @package angie.migrations
 */
class MigrateControlTowerSettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('control_tower_check_reply_to_comment');
        $this->removeConfigOption('control_tower_check_email_queue');
        $this->removeConfigOption('control_tower_check_email_conflicts');

        $this->removeConfigOption('control_tower_check_scheduled_tasks');
        $this->removeConfigOption('control_tower_check_disk_usage');
        $this->removeConfigOption('control_tower_check_performance');
    }
}
