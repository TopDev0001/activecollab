<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Clean-up users table.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCleanUpUsersTable extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $users = $this->useTableForAlter('users');

        foreach (['homescreen_id', 'role_id', 'note', 'auto_assign'] as $field) {
            if ($users->getColumn($field)) {
                $users->dropColumn($field);
            }
        }

        $users->dropColumn('updated_by_id');
        $users->dropColumn('updated_by_name');
        $users->dropColumn('updated_by_email');

        $this->doneUsingTables();
    }
}
