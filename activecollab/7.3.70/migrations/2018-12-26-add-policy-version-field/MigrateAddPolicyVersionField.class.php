<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddPolicyVersionField extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('users')) {
            $users = $this->useTableForAlter('users');

            if (!$users->getColumn('policy_version')) {
                $users->addColumn(
                    new DBEnumColumn('policy_version', ['january_2019'], null),
                    'company_id'
                );
            }

            $this->doneUsingTables();
        }
    }
}
