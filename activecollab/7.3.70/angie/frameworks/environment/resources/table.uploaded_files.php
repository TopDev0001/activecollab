<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('uploaded_files')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn('LocalUploadedFile'),
        new DBFileMetaColumn(),
        new DBStringColumn('code', 50),
        new DBCreatedOnByColumn(true),
        new DBIpAddressColumn('ip_address'),
        new DBAdditionalPropertiesColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('code', DBIndex::UNIQUE),
    ]
);
