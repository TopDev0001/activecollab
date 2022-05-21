<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('feature_pointer_dismissals')->addColumns(
    [
        DBIntegerColumn::create('feature_pointer_id', DBColumn::NORMAL, 0)->setUnsigned(true),
        DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
    ]
)->addIndices(
    [
        new DBIndexPrimary(['feature_pointer_id', 'user_id']),
        DBIndex::create('user_id'),
    ]
);
