<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * User addresses table definition.
 *
 * @package angie.frameworks.authentication
 * @subpackage resources
 */

return DB::createTable('user_addresses')->addColumns([
    DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
    DBStringColumn::create('email', 150, ''),
])->addIndices([
    new DBIndexPrimary(['user_id', 'email']),
]);
