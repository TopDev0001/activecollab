<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('conversations')
    ->addColumns(
        [
            new DBIdColumn(),
            new DBTypeColumn(),
            new DBStringColumn('name'),
            new DBParentColumn(),
            (new DBDateTimeColumn('last_message_on'))->setDefault(null),
            new DBCreatedOnByColumn(),
            new DBUpdatedOnByColumn(),
        ]
    );
