<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFixAddPaidOnFieldToAll extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('users')) {
            $users = $this->useTableForAlter('users');

            if (!$users->getColumn('paid_on')) {
                $users->addColumn(new DBDateTimeColumn('paid_on'), 'first_login_on');
            }

            $this->doneUsingTables();
        }
    }
}
