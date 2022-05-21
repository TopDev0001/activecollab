<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce alternative user addresses.
 *
 * @package angie.migrations
 */
class MigrateIntroduceUserAddresses extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->createTable('user_addresses', [
            DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
            DBStringColumn::create('email', 150, ''),
        ], [
            new DBIndexPrimary(['user_id', 'email']),
        ]);
    }
}
