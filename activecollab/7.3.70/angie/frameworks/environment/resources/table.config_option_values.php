<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('config_option_values')->addColumns(
    [
        new DBNameColumn(50),
        new DBParentColumn(true, false),
        new DBTextColumn('value'),
    ]
)->addIndices(
    [
        new DBIndexPrimary(['name', 'parent_type', 'parent_id']),
    ]
);
