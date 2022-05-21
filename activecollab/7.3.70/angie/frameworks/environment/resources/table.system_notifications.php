<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('system_notifications')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn(),
        new DBFkColumn('recipient_id', 0, true),
        new DBDateTimeColumn('created_on'),
        new DBBoolColumn('is_dismissed', false),
        new DBAdditionalPropertiesColumn(),
    ]
);
