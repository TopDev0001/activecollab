<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove context field from activity logs.
 *
 * @package angie.migrations
 */
class MigrateRemoveContextFromActivityLogs extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->loadTable('activity_logs')->dropColumn('subject_context');
    }
}
