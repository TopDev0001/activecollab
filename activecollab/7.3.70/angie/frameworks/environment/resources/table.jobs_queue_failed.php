<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('jobs_queue_failed')->addColumns(
    [
        (new DBIdColumn())
            ->setSize(DBColumn::BIG),
        new DBTypeColumn(),
        new DBStringColumn('channel', DBStringColumn::MAX_LENGTH, 'main'),
        (new DBIntegerColumn('batch_id', 10))
            ->setUnsigned(true),
        new DBJsonColumn('data'),
        new DBDateTimeColumn('failed_at'),
        new DBStringColumn('reason', DBStringColumn::MAX_LENGTH, ''),
    ]
)->addIndices(
    [
        new DBIndex('channel'),
        new DBIndex('batch_id'),
        new DBIndex('failed_at'),
    ]
);
