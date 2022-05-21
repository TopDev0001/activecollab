<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFailedJobsLog extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('jobs_queue_failed')) {
            return;
        }

        $this->createTable(
            DB::createTable('jobs_queue_failed')->addColumns(
                [
                    (new DBIdColumn())
                        ->setSize(DBColumn::BIG),
                    new DBTypeColumn(),
                    new DBTextColumn('data'),
                    new DBDateTimeColumn('failed_at'),
                    DBStringColumn::create('reason', DBStringColumn::MAX_LENGTH, ''),
                ]
            )->addIndices(
                [
                    DBIndex::create('failed_at'),
                ]
            )
        );
    }
}
