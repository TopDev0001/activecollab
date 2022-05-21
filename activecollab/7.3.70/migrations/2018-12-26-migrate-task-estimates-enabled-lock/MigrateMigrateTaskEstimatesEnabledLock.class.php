<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateMigrateTaskEstimatesEnabledLock extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('task_estimates_enabled_lock')) {
            $this->addConfigOption('task_estimates_enabled_lock', false);
        }
    }
}
