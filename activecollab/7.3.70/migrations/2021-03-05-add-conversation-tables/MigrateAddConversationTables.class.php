<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddConversationTables extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('conversations')) {
            $this->createTable(
                DB::createTable('conversations')->addColumns(
                    [
                        new DBIdColumn(),
                        new DBTypeColumn(),
                        new DBStringColumn('name'),
                        new DBParentColumn(),
                        new DBCreatedOnByColumn(),
                        new DBUpdatedOnByColumn(),
                    ]
                )
            );
        }

        if (!$this->tableExists('conversation_users')) {
            $this->createTable(
                DB::createTable('conversation_users')->addColumns(
                    [
                        new DBIdColumn(),
                        DBFkColumn::create('conversation_id', 0, true),
                        DBFkColumn::create('user_id', 0, true),
                        new DBBoolColumn('is_muted'),
                        new DBCreatedOnColumn(),
                        new DBUpdatedOnColumn(),
                    ]
                )->addIndices(
                    [
                        DBIndex::create(
                            'conversation_user',
                            DBIndex::UNIQUE,
                            [
                                'conversation_id',
                                'user_id',
                            ]
                        ),
                        DBIndex::create('user_id'),
                    ]
                )
            );
        }
    }
}
