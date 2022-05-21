<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddTableShortcuts extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('shortcuts')) {
            $this->createTable(
                'shortcuts',
                [
                    new DBIdColumn(),
                    DBStringColumn::create('name'),
                    new DBTextColumn('url'),
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
                    new DBCreatedOnByColumn(true, true),
                ]
            );
        }
    }
}
