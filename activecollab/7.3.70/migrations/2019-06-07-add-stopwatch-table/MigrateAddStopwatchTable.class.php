<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddStopwatchTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('stopwatches')) {
            $this->createTable('stopwatches', [
                new DBIdColumn(),
                new DBParentColumn(),
                new DBUserColumn('user'),
                new DBDateTimeColumn('started_on'),
                DBIntegerColumn::create('is_kept', 0)->setSize(DBColumn::TINY)->setDefault(0),
                DBIntegerColumn::create('elapsed', 50, 0),
                new DBCreatedOnColumn(),
                new DBUpdatedOnColumn(),
            ]);
        }
    }
}
