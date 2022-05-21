<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce centralized timer settings.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateCentralTimerSettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('timer_minimal_time_entry', 15);
        $this->addConfigOption('timer_rounding_interval', 15);
        $this->addConfigOption('timer_on_start_change_label_to');
        $this->addConfigOption('timer_on_done_change_label_to');
    }
}
