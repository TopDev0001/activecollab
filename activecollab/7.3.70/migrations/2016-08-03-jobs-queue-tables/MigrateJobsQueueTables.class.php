<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateJobsQueueTables extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('job_batches')) {
            $this->createTable(
                DB::createTable('job_batches')->addColumns(
                    [
                        new DBIdColumn(),
                        DBStringColumn::create('name'),
                        DBIntegerColumn::create('jobs_count', 10, 0)->setUnsigned(true),
                        new DBDateTimeColumn('created_at'),
                    ]
                )->addIndices(
                    [
                        DBIndex::create('created_at'),
                    ]
                )
            );
        }

        $jobs_queue_table = $this->useTableForAlter('jobs_queue');

        $jobs_queue_table->addColumn(DBIntegerColumn::create('batch_id', 10)->setUnsigned(true), 'channel');
        $jobs_queue_table->addIndex(DBIndex::create('batch_id'));

        $jobs_queue_failed_table = $this->useTableForAlter('jobs_queue_failed');

        $jobs_queue_failed_table->addColumn(DBIntegerColumn::create('batch_id', 10)->setUnsigned(true), 'channel');
        $jobs_queue_failed_table->addIndex(DBIndex::create('batch_id'));

        $email_log = $this->useTableForAlter('email_log');

        if (!$email_log->getIndex('sent_on')) {
            $email_log->addIndex(new DBIndex('sent_on'));
        }
    }
}
