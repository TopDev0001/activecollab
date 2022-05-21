<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop password expires on.
 *
 * @package angie.migrations
 */
class MigrateDropPasswordExpiresOn extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $users = $this->useTableForAlter('users');

        if ($users->getColumn('password_expires_on')) {
            $users->dropColumn('password_expires_on');
        }

        $this->doneUsingTables();
    }
}
