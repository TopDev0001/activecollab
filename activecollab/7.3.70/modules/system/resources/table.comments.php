<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('comments')
    ->addColumns(
        [
            new DBIdColumn(),
            DBStringColumn::create('source', 50),
            new DBParentColumn(),
            new DBBodyColumn(),
            new DBIpAddressColumn('ip_address'),
            new DBCreatedOnByColumn(true, true),
            new DBUpdatedOnByColumn(),
            new DBTrashColumn(true),
        ]
    );
