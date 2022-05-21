<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateStorageUsageStats extends AngieModelMigration
{
    public function up()
    {
        $this->removeConfigOption('disk_space_limit');
        $this->removeConfigOption('disk_space_email_notifications');
        $this->removeConfigOption('disk_space_low_space_threshold');
        $this->removeConfigOption('disk_space_old_versions_size');
    }
}
