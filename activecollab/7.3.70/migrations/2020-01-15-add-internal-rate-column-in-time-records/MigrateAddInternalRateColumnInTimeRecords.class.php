<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddInternalRateColumnInTimeRecords extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('time_records')) {
            $timeRecords = $this->useTableForAlter('time_records');

            if (!$timeRecords->getColumn('internal_rate')) {
                $timeRecords->addColumn(DBMoneyColumn::create('internal_rate', 13, 3, 0));
            }

            $this->doneUsingTables();
        }
    }
}
