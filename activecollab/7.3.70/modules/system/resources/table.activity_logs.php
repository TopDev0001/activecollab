<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('activity_logs')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn(ActivityLog::class),
        new DBParentColumn(),
        DBStringColumn::create('parent_path', DBStringColumn::MAX_LENGTH, ''),
        new DBCreatedOnByColumn(true, true),
        new DBUpdatedOnColumn(),
        new DBAdditionalPropertiesColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('parent_path', DBIndex::KEY, ['parent_path', 'parent_id']),
    ]
);
