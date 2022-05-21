<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateIntroduceUserInvitationsModel extends AngieModelMigration
{
    public function up()
    {
        $this->createTable(
            DB::createTable('user_invitations')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('user_id', 10, '0')->setUnsigned(true),
                    DBStringColumn::create('code', 20, ''),
                    new DBDateTimeColumn('invited_on'),
                    new DBDateTimeColumn('accepted_on'),
                ]
            )->addIndices(
                [
                    DBIndex::create('user_id', DBIndex::UNIQUE),
                ]
            )
        );

        $users = $this->useTableForAlter('users');

        $users->dropColumn('invited_on');

        [$config_options, $config_option_values] = $this->useTables('config_options', 'config_option_values');

        $this->execute("DELETE FROM $config_options WHERE name = 'welcome_message'");
        $this->execute("DELETE FROM $config_option_values WHERE name = 'welcome_message'");

        $this->doneUsingTables();
    }
}
