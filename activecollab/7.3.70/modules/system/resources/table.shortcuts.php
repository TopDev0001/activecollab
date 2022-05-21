<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('shortcuts')
    ->addColumns(
        [
            new DBIdColumn(),
            DBStringColumn::create('name'),
            new DBTextColumn('url'),
            (new DBTextColumn('relative_url'))->setDefault(null),
            new DBEnumColumn(
                'icon',
                [
                    'insert_link',
                    'project',
                    'report',
                    'checkbox-blank-toggler',
                    'calendar',
                    'note',
                    'pencil',
                    'dollar_document',
                    'person',
                    'labels',
                    'settings',
                ],
                'insert_link'
            ),
            DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
            new DBUpdatedOnColumn(),
            new DBCreatedOnByColumn(true, true),
        ]
    );
