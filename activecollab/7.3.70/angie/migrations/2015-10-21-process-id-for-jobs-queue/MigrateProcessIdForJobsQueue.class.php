<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add process_id column to jobs_queue table.
 *
 * @package angie.migrations
 */
class MigrateProcessIdForJobsQueue extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $jobs_queue = $this->useTableForAlter('jobs_queue');

        if (!$jobs_queue->getColumn('process_id')) {
            $jobs_queue->addColumn(DBIntegerColumn::create('process_id', 10, 0)->setUnsigned(true), 'attempts');
        }

        $this->doneUsingTables();
    }
}
