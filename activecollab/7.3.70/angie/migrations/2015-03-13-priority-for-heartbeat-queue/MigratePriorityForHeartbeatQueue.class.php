<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add priority field to heartbeat queue.
 *
 * @package angie.migrations
 */
class MigratePriorityForHeartbeatQueue extends AngieModelMigration
{
    /**
     * Migreate up.
     */
    public function up()
    {
        $this->useTableForAlter('heartbeat_queue')->addColumn(DBIntegerColumn::create('priority', 10, 0)->setUnsigned(true), 'json');
        $this->doneUsingTables();
    }
}
