<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('executed_model_migrations')->addColumns(
    [
        (new DBIdColumn())
            ->setSize(DBColumn::SMALL),
        DBStringColumn::create('migration', DBStringColumn::MAX_LENGTH, ''),
        new DBDateColumn('changeset_timestamp'),
        DBStringColumn::create('changeset_name', DBStringColumn::MAX_LENGTH),
        new DBDateTimeColumn('executed_on'),
    ]
)->addIndices(
    [
        DBIndex::create('migration', DBIndex::UNIQUE, 'migration'),
        DBIndex::create('executed_on'),
    ]
);
