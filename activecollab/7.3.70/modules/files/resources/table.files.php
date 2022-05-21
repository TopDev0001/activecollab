<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('files')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn('File'),
        (new DBIntegerColumn('project_id', DBColumn::NORMAL, 0))
            ->setUnsigned(true),
        new DBFileMetaColumn(),
        new DBBoolColumn('is_hidden_from_clients'),
        new DBTrashColumn(true),
        new DBCreatedOnByColumn(true, true),
        new DBUpdatedOnByColumn(true, true),
        new DBAdditionalPropertiesColumn(),
        (new DBTextColumn('search_content'))
            ->setSize(DBTextColumn::BIG),
    ]
)->addIndices(
    [
        DBIndex::create('project_id'),
        DBIndex::create('name'),
    ]
);
