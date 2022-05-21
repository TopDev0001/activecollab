<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateHeartbeatQueue extends AngieModelMigration
{
    public function up()
    {
        $this->createTable(
            DB::createTable('heartbeat_queue')->addColumns(
                [
                    (new DBIdColumn())
                        ->setSize(DBColumn::BIG),
                    new DBStringColumn('hash', 40),
                    (new DBTextColumn('json'))->setSize(DBColumn::BIG),
                ]
            )->addIndices(
                [
                    DBIndex::create('hash', DBIndex::UNIQUE),
                ]
            )
        );

        $this->addConfigOption('heartbeat_incoming_key');
        $this->addConfigOption('heartbeat_outgoing_backend_key');
        $this->addConfigOption('heartbeat_outgoing_frontend_key');
    }
}
