<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add conflict notification users config option.
 *
 * @package angie.migrations
 */
class MigrateAddConflictNotificationUsersConfigOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('conflict_notifications_users');
    }
}
