<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigratePrepareEmptyLastLoginOn extends AngieModelMigration
{
    public function up()
    {
        $users = $this->useTableForAlter('users');

        if (!$users->getColumn('last_login_on')) {
            $this->execute('ALTER TABLE users ADD last_login_on DATETIME NULL');
            $this->execute('ALTER TABLE users ADD INDEX(last_login_on)');
        }
    }
}
