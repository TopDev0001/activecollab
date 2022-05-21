<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateHeartbeatWithJobs extends AngieModelMigration
{
    public function up()
    {
        $this->dropTable('heartbeat_queue');

        if (!$this->tableExists('jobs_queue')) {
            $this->createTable(
                DB::createTable('jobs_queue')->addColumns(
                    [
                        (new DBIdColumn())
                            ->setSize(DBColumn::BIG),
                        new DBTypeColumn(),
                        DBIntegerColumn::create('priority')->setUnsigned(true),
                        new DBTextColumn('data'),
                        new DBDateTimeColumn('available_at'),
                        DBStringColumn::create('reservation_key', 40),
                        new DBDateTimeColumn('reserved_at'),
                        DBIntegerColumn::create('attempts', 5)->setUnsigned(true),
                    ]
                )->addIndices(
                    [
                        DBIndex::create('reservation_key', DBIndex::UNIQUE),
                        DBIndex::create('priority'),
                        DBIndex::create('reserved_at'),
                    ]
                )
            );
        }
    }
}
