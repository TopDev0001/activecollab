<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('categories')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn(Category::class),
        new DBParentColumn(),
        new DBNameColumn(100),
        new DBCreatedOnByColumn(),
        new DBUpdatedOnColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('name', DBIndex::UNIQUE, ['parent_type', 'parent_id', 'type', 'name']),
    ]
);
