<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * User table definition.
 *
 * @package angie.frameworks.authentication
 * @subpackage resources
 */

return DB::createTable('reactions')->addColumns([
    new DBIdColumn(),
    new DBTypeColumn(),
    new DBParentColumn(),
    new DBCreatedOnByColumn(true, true),
]);
