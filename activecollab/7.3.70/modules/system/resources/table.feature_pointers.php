<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('feature_pointers')->addColumns([
    new DBIdColumn(),
    new DBTypeColumn('FeaturePointer'),
    DBIntegerColumn::create('parent_id', DBColumn::NORMAL)->setUnsigned(true),
    new DBDateColumn('expires_on'),
    new DBCreatedOnColumn(),
])->addIndices([
    DBIndex::create('parent_id'),
]);
