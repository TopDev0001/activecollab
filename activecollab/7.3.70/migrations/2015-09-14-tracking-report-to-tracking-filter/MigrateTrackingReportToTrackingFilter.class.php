<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Change the name of saved tracking report filters.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTrackingReportToTrackingFilter extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('UPDATE data_filters SET type = ? WHERE type = ?', 'TrackingFilter', 'TrackingReport');
    }
}
