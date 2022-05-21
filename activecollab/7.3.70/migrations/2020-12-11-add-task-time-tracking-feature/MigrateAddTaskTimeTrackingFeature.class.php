<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddTaskTimeTrackingFeature extends AngieModelMigration
{
    public function up()
    {
        if (!$this->getConfigOptionValue('task_time_tracking_enabled')) {
            $this->addConfigOption('task_time_tracking_enabled', true);
        }
        if (!$this->getConfigOptionValue('task_time_tracking_enabled_lock')) {
            $this->addConfigOption('task_time_tracking_enabled_lock', true);
        }
    }
}
