<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('security_logs')->addColumns(
    [
        (new DBIdColumn())
            ->setSize(DBColumn::BIG),
        new DBEnumColumn('event', ['login_attempt', 'login', 'logout']),
        new DBUserColumn('user'),
        new DBIpAddressColumn('user_ip'),
        new DBTextColumn('user_agent'),
        new DBDateTimeColumn('created_on'),
    ]
)->addIndices(
    [
        DBIndex::create('event'),
        DBIndex::create('created_on'),
        DBIndex::create('user_ip'),
    ]
);
