<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('attachments')->addColumns(
    [
        new DBIdColumn(),
        new DBTypeColumn('Attachment'),
        new DBParentColumn(),
        new DBFileMetaColumn(),
        new DBEnumColumn(
            'disposition',
            [
                'attachment',
                'inline',
            ],
            'attachment'
        ),
        new DBCreatedOnByColumn(true),
        new DBAdditionalPropertiesColumn(),
        (new DBTextColumn('search_content'))
            ->setSize(DBTextColumn::BIG),
    ]
);
