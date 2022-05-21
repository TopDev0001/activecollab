<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateIntroduceTeams extends AngieModelMigration
{
    public function up()
    {
        $this->createTable(
            DB::createTable('teams')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(100, true),
                    new DBCreatedOnByColumn(),
                    new DBUpdatedOnColumn(),
                ]
            )
        );

        $this->createTable(
            DB::createTable('team_users')->addColumns(
                [
                    DBIntegerColumn::create('team_id', DBColumn::NORMAL, 0),
                    DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0),
                ]
            )->addIndices(
                [
                    new DBIndexPrimary(['team_id', 'user_id']),
                    DBIndex::create('user_id'),
                ]
            )
        );
    }
}
