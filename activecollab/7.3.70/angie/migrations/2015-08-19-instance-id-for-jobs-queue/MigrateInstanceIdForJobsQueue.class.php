<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add instance_id column to jobs_queue table.
 *
 * @package angie.migrations
 */
class MigrateInstanceIdForJobsQueue extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $jobs_queue = $this->useTableForAlter('jobs_queue');

        if (!$jobs_queue->getColumn('instance_id')) {
            $jobs_queue->addColumn(DBIntegerColumn::create('instance_id', 10, 0)->setUnsigned(true), 'type');
            $jobs_queue->addIndex(DBIndex::create('instance_id'));
        }

        $email_log = $this->useTableForAlter('email_log');

        if (!$email_log->getColumn('instance_id')) {
            $email_log->addColumn(DBIntegerColumn::create('instance_id', 10, 0)->setUnsigned(true), 'id');
            $email_log->addIndex(DBIndex::create('instance_id'));
        }

        $this->doneUsingTables();
    }
}
