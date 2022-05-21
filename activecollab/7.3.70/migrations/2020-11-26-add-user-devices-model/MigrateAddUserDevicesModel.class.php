<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddUserDevicesModel extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('user_devices')) {
            $this->createTable(
                DB::createTable('user_devices')->addColumns([
                    new DBIdColumn(),
                    new DBStringColumn('token'),
                    new DBStringColumn('unique_key', 128),
                    new DBStringColumn('manufacturer', 64),
                    DBIntegerColumn::create('user_id', DBColumn::NORMAL)->setUnsigned(true),
                    new DBCreatedOnColumn(),
                    new DBUpdatedOnColumn(),
                ])->addIndices([
                    DBIndex::create('user_id'),
                ])
            );
        }
    }
}
