<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('api_subscriptions')->addColumns(
    [
        new DBIdColumn(),
        DBFkColumn::create('user_id', 0, true),
        DBStringColumn::create('token_id', 191),
        DBStringColumn::create('client_name', 100),
        DBStringColumn::create('client_vendor', 100),
        new DBCreatedOnColumn(),
        new DBDateTimeColumn('last_used_on'),
        DBIntegerColumn::create('requests_count', 10, 1)->setUnsigned(true),
    ]
)->addIndices(
    [
        DBIndex::create('token_id', DBIndex::UNIQUE),
        DBIndex::create('last_used_on'),
    ]
);
