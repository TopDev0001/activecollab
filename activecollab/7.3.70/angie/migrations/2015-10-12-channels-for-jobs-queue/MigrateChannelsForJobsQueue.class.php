<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add channels to jobs queue.
 *
 * @package angie.migrations
 */
class MigrateChannelsForJobsQueue extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $jobs_queue = $this->useTableForAlter('jobs_queue');
        $jobs_queue_failed = $this->useTableForAlter('jobs_queue_failed');

        /** @var DBTable $table */
        foreach ([$jobs_queue, $jobs_queue_failed] as $table) {
            if (!$table->getColumn('channel')) {
                $table->addColumn(DBStringColumn::create('channel', DBStringColumn::MAX_LENGTH, 'main'), 'type');
            }

            if (!$table->indexExists('channel')) {
                $table->addIndex(DBIndex::create('channel'));
            }
        }

        $this->doneUsingTables();
    }
}
