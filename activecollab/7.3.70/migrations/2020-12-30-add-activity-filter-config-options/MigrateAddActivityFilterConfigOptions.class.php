<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddActivityFilterConfigOptions extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('my_work_activity_filter')) {
            $this->addConfigOption('my_work_activity_filter', 'daily');
        }
        if (!ConfigOptions::exists('global_activity_filter')) {
            $this->addConfigOption('global_activity_filter', 'daily');
        }
        if (!ConfigOptions::exists('project_activity_filter')) {
            $this->addConfigOption('project_activity_filter', 'weekly');
        }
    }
}
