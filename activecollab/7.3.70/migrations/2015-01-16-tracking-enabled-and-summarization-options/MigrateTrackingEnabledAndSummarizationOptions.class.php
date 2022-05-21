<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate tracking enabled and time record summarization options.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTrackingEnabledAndSummarizationOptions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('default_is_tracking_enabled', false);
        $this->addConfigOption('default_tracking_records_summarization', 'sum_all_by_task');
    }
}
