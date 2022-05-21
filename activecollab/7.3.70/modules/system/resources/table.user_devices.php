<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('user_devices')->addColumns([
    new DBIdColumn(),
    new DBStringColumn('token'),
    new DBStringColumn('unique_key', 128),
    new DBStringColumn('manufacturer', 64),
    DBIntegerColumn::create('user_id', DBColumn::NORMAL)->setUnsigned(true),
    new DBCreatedOnColumn(),
    new DBUpdatedOnColumn(),
])->addIndices([
    DBIndex::create('user_id'),
]);
