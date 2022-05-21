<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('labels')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn('Label'),
        new DBNameColumn(255, true, 'type'),
        DBStringColumn::create('color', 50),
        new DBUpdatedOnColumn(),
        new DBBoolColumn('is_default'),
        DBIntegerColumn::create('position', DBColumn::NORMAL, 0)->setUnsigned(true),
    ]
)->addIndices(
    [
        DBIndex::create('position'),
    ]
);
