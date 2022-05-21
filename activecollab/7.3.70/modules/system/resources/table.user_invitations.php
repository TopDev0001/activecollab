<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('user_invitations')->addColumns(
    [
        new DBIdColumn(),
        DBIntegerColumn::create('user_id', 10, '0')->setUnsigned(true),
        new DBRelatedObjectColumn('invited_to', false),
        DBStringColumn::create('code', 20, ''),
        new DBCreatedOnByColumn(),
        new DBUpdatedOnColumn(),
    ]
)->addIndices(
    [
        DBIndex::create('user_id', DBIndex::UNIQUE),
    ]
);
