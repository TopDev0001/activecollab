<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('companies')
    ->addColumns(
        [
            new DBIdColumn(),
            new DBNameColumn(100),
            new DBTextColumn('address'),
            DBStringColumn::create('homepage_url'),
            DBStringColumn::create('phone'),
            new DBTextColumn('note'),
            DBIntegerColumn::create('currency_id', DBIntegerColumn::NORMAL, null)->setUnsigned(true),
            DBStringColumn::create('tax_id'),
            new DBCreatedOnByColumn(),
            new DBUpdatedOnByColumn(),
            new DBArchiveColumn(false, true),
            new DBTrashColumn(),
            new DBBoolColumn('is_owner'),
        ]
    )->addIndices(
        [
            DBIndex::create('name'),
        ]
    );
