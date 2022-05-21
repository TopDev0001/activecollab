<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add default filter projects config option.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTrackingReportsConfigOptions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('filter_period_tracking_report', 'monthly');
        $this->addConfigOption('filter_period_payments_report', 'monthly');
    }
}
