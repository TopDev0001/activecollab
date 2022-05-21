<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('notification_recipients')->addColumns(
    [
        new DBIdColumn(),
        DBIntegerColumn::create('notification_id')->setUnsigned(true),
        new DBUserColumn('recipient'),
        new DBDateTimeColumn('read_on'),
        new DBBoolColumn('is_mentioned'),
    ]
)->addIndices(
    [
        DBIndex::create(
            'notification_recipient',
            DBIndex::UNIQUE,
            [
                'notification_id',
                'recipient_email',
            ]
        ),
    ]
);
