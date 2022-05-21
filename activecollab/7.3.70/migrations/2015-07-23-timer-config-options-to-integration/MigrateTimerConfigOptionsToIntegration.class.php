<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate Timer settings to integration.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTimerConfigOptionsToIntegration extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('timer_minimal_time_entry');
        $this->removeConfigOption('timer_rounding_interval');
        $this->removeConfigOption('timer_on_start_change_label_to');
    }
}
