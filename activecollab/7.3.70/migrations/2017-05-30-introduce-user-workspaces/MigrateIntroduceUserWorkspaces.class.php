<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce concept of user workspaces.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateIntroduceUserWorkspaces extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('user_workspaces')) {
            $this->createTable(
                DB::createTable('user_workspaces')->addColumns(
                    [
                        new DBIdColumn(),
                        DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                        DBIntegerColumn::create('shepherd_account_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                        DBStringColumn::create('shepherd_account_type', 150),
                        DBStringColumn::create('shepherd_account_url', 150),
                        new DBNameColumn(150),
                        new DBBoolColumn('is_shown_in_launcher', true),
                        new DBBoolColumn('is_owner', true),
                        DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
                        new DBDateTimeColumn('updated_on'),
                    ]
                )->addIndices(
                    [
                        DBIndex::create('user_id', DBIndex::KEY),
                    ]
                )
                    ->addModelTrait('IUpdatedOn', 'IUpdatedOnImplementation')
            );

            [$users_table, $user_workspaces_table] = $this->useTables('users', 'user_workspaces');

            if ($users = $this->execute("SELECT id, type FROM $users_table ORDER BY id ASC")) {
                $account_id = 1;

                foreach (['getAccountId', 'getInstanceId'] as $method_name) {
                    if (method_exists(AngieApplication::class, $method_name)) {
                        $account_id = call_user_func(
                            [
                                AngieApplication::class, $method_name,
                            ]
                        );

                        break;
                    }
                }

                foreach ($users as $user) {
                    $this->execute("INSERT INTO $user_workspaces_table (user_id, shepherd_account_id, shepherd_account_type, shepherd_account_url, name, is_shown_in_launcher, is_owner, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        $user['id'],
                        $account_id,
                        'ActiveCollab\Shepherd\Model\Account\ActiveCollab\FeatherAccount', // TODO
                        'app.activecollab.com/' . $account_id, // TODO
                        '#' . $account_id,
                        1,
                        $user['type'] === 'Owner',
                        1
                    );
                }
            }
        }
    }

    /**
     * Migrate down.
     */
    public function down()
    {
        $this->dropTable('users', 'user_workspaces');
    }
}
