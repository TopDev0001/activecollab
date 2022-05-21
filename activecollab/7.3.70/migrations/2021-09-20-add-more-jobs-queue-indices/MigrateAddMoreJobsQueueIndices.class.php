<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddMoreJobsQueueIndices extends AngieModelMigration
{
    public function up()
    {
        $jobs_queue = $this->useTableForAlter('jobs_queue');

        if (!$jobs_queue->getIndex('available_at')) {
            $jobs_queue->addIndex(new DBIndex('available_at'));
        }

        if (!$jobs_queue->getIndex('available_in_channel')) {
            $jobs_queue->addIndex(
                new DBIndex(
                    'available_in_channel',
                    DBIndex::KEY,
                    [
                        'channel',
                        'available_at',
                    ]
                )
            );
        }

        if (!$jobs_queue->getIndex('available_in_channel_r')) {
            $jobs_queue->addIndex(
                new DBIndex(
                    'available_in_channel_r',
                    DBIndex::KEY,
                    [
                        'reserved_at',
                        'channel',
                        'available_at',
                    ]
                )
            );
        }
    }
}
