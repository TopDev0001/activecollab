<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateUpdateUserEmailFiledToUnique extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('users')) {
            $users = $this->useTableForAlter('users');
            $users->alterColumn('email', DBStringColumn::create('email', 150));
            $users->alterIndex('email', DBIndex::create('email', DBIndex::UNIQUE, 'email'));
        }
    }
}
