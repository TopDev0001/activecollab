<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddFirstLoginOnToUsersTable extends AngieModelMigration
{
    public function up()
    {
        $users = $this->useTableForAlter('users');

        // Drop old column if exists
        if ($users->getColumn('invitation_accepted_on')){
            $users->dropColumn('invitation_accepted_on');
        }

        if (!$users->getColumn('first_login_on')) {
            $this->execute('ALTER TABLE users ADD first_login_on DATETIME NULL AFTER raw_additional_properties');
            $this->execute('UPDATE users SET first_login_on = created_on WHERE last_login_on IS NOT NULL');
        }
    }
}
