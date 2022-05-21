<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('config_options')->addColumns(
    [
        new DBIdColumn(),
        new DBNameColumn(100),
        new DBTextColumn('value'),
        new DBUpdatedOnColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('name', DBIndex::UNIQUE),
    ]
);
