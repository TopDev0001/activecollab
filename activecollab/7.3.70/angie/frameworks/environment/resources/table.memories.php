<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('memories')->addColumns(
    [
        new DBIdColumn(),
        new DBStringColumn('key', 191, ''),
        (new DBTextColumn('value'))
            ->setSize(DBTextColumn::MEDIUM),
        new DBUpdatedOnColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('key', DBIndex::UNIQUE),
    ]
);
