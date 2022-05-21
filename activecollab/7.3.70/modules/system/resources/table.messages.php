<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('messages')
    ->addColumns(
        [
            new DBIdColumn(),
            DBIntegerColumn::create(
                'order_id',
                DBColumn::NORMAL,
                0
            )->setUnsigned(true)->setSize(DBColumn::BIG),
            new DBTypeColumn(),
            DBFkColumn::create('conversation_id', 0, true),
            new DBBodyColumn(true, false),
            new DBDateTimeColumn('changed_on'),
            new DBCreatedOnByColumn(false, true),
            new DBUpdatedOnColumn(),
            new DBAdditionalPropertiesColumn(),
        ]
    )->addIndices(
        [
            DBIndex::create('order_id'),
            DBIndex::create('conversation_id'),
        ]
    );
