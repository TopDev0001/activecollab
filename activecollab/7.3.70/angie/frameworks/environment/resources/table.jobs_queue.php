<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('jobs_queue')->addColumns(
    [
        (new DBIdColumn())
            ->setSize(DBColumn::BIG),
        new DBTypeColumn(),
        new DBStringColumn('channel', DBStringColumn::MAX_LENGTH, 'main'),
        (new DBIntegerColumn('batch_id', 10))
            ->setUnsigned(true),
        new DBJsonColumn('data'),
        new DBDateTimeColumn('available_at'),
        new DBStringColumn('reservation_key', 40),
        new DBDateTimeColumn('reserved_at'),
        (new DBIntegerColumn('attempts', DBColumn::SMALL))
            ->setUnsigned(true),
        (new DBIntegerColumn('process_id', 10, 0))
            ->setUnsigned(true),
    ]
)->addIndices(
    [
        new DBIndex('batch_id'),
        new DBIndex('channel'),
        new DBIndex('reservation_key', DBIndex::UNIQUE),
        new DBIndex('reserved_at'),
        new DBIndex('available_at'),
        new DBIndex(
            'available_in_channel',
            DBIndex::KEY,
            [
                'channel',
                'available_at',
            ]
        ),
        new DBIndex(
            'available_in_channel_r',
            DBIndex::KEY,
            [
                'reserved_at',
                'channel',
                'available_at',
            ]
        ),
    ]
);
