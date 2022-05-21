<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop timer_on_done_change_label_to configuration option.
 *
 * @package ActiveCollab.migrations
 */
class MigrateRemoveTimerChangeLabelOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('timer_on_done_change_label_to');
    }
}
