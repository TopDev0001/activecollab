<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * API subscriptions table definition.
 *
 * @package ActiveCollab.modules.system
 * @subpackage resources
 */

return DB::createTable('user_sessions')->addColumns(
    [
        new DBIdColumn(),
        DBFkColumn::create('user_id', 0, true),
        DBStringColumn::create('session_id', 191),
        DBIntegerColumn::create('session_ttl', 10, 0)->setUnsigned(true),
        DBStringColumn::create('csrf_validator', 191),
        new DBCreatedOnColumn(),
        new DBDateTimeColumn('last_used_on'),
        DBIntegerColumn::create('requests_count', 10, 1)->setUnsigned(true),
    ]
)->addIndices(
    [
        DBIndex::create('session_id', DBIndex::UNIQUE, 'session_id'),
        DBIndex::create('csrf_validator'),
        DBIndex::create('last_used_on'),
    ]
);
