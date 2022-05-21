<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove user model timestamps that are no longer needed.
 *
 * @package angie.migrations
 */
class MigrateCleanupUserTimestamps extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $users = $this->useTableForAlter('users');

        $users->dropColumn('last_visit_on');
        $users->dropColumn('last_login_on');
        $users->dropColumn('last_activity_on');

        $this->doneUsingTables();
    }
}
