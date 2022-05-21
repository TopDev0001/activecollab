<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('notifications')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn('Notification'),
        new DBParentColumn(),
        new DBUserColumn('sender'),
        new DBCreatedOnColumn(),
        new DBAdditionalPropertiesColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('created_on'),
    ]
);
