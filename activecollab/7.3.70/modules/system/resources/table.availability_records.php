<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('availability_records')->addColumns(
    [
        new DBIdColumn(),
        DBIntegerColumn::create('availability_type_id', 10, 0)->setUnsigned(true),
        DBIntegerColumn::create('user_id', 10, 0)->setUnsigned(true),
        DBStringColumn::create('message', 255),
        new DBDateColumn('start_date'),
        new DBDateColumn('end_date'),
        new DBCreatedOnByColumn(),
        new DBUpdatedOnColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('availability_type_id'),
        DBIndex::create('user_id'),
    ]
);
