<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddMessagesTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('messages')) {
            $this->createTable(
                DB::createTable('messages')->addColumns(
                    [
                        new DBIdColumn(),
                        DBFkColumn::create('conversation_id', 0, true),
                        (new DBTextColumn('body'))->setSize(DBTextColumn::BIG),
                        new DBEnumColumn(
                            'body_mode',
                            [
                                'paragraph',
                                'break-line',
                            ],
                            'paragraph'
                        ),
                        new DBCreatedOnByColumn(false, true),
                        new DBUpdatedOnColumn(),
                    ]
                )->addIndices(
                    [
                        DBIndex::create('conversation_id'),
                    ]
                )
            );
        }
    }
}
