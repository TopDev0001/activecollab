<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddJobTypeHourlyRateColumnInTimeRecord extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('time_records')) {
            $timeRecords = $this->useTableForAlter('time_records');
            $job_types = $this->useTableForAlter('job_types');

            if (!$timeRecords->getColumn('job_type_hourly_rate')) {
                $timeRecords->addColumn(DBMoneyColumn::create('job_type_hourly_rate', 13, 3, 0));
                $this->execute('UPDATE ' . $timeRecords->getName() . ' TR, ' . $job_types->getName() . ' JT 
                SET TR.job_type_hourly_rate = JT.default_hourly_rate 
                WHERE TR.job_type_id = JT.id'
                );
            }
        }
    }
}
