<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('webhooks')
    ->addColumns(
        [
            new DBIdColumn(),
            new DBTypeColumn(Webhook::class),
            new DBFkColumn('integration_id'),
            new DBNameColumn(100),
            new DBStringColumn('url'),
            new DBBoolColumn('is_enabled'),
            new DBEnumColumn(
                'priority',
                [
                    'low',
                    'normal',
                    'high',
                ],
                'high'
            ),
            new DBStringColumn('secret'),
            new DBTextColumn('filter_event_types'),
            new DBTextColumn('filter_projects'),
            new DBTextColumn('filter_assignees'),
            new DBCreatedOnByColumn(true, true),
        ]
    );
