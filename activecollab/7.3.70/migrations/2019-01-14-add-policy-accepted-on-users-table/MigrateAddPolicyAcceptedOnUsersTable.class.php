<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddPolicyAcceptedOnUsersTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('users')) {
            $users = $this->useTableForAlter('users');

            if (!$users->getColumn('policy_accepted_on')) {
                $users->addColumn(
                    new DBDateTimeColumn('policy_accepted_on'),
                    'policy_version'
                );
            }

            $this->doneUsingTables();
        }
    }
}
