<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddRecurringTasksFeature extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('recurring_tasks_enabled')) {
            $this->addConfigOption('recurring_tasks_enabled', true);
        }
        if (!ConfigOptions::exists('recurring_tasks_enabled_lock')) {
            $this->addConfigOption('recurring_tasks_enabled_lock', true);
        }
    }
}
