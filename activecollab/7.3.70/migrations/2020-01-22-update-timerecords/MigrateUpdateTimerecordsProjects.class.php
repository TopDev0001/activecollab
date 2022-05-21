<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateUpdateTimerecordsProjects extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('time_records')) {
            $timeRecords = $this->useTableForAlter('time_records');
            $job_types = $this->useTableForAlter('job_types');

            if (!$timeRecords->getColumn('job_type_hourly_rate')) {
                $timeRecords->addColumn(DBMoneyColumn::create('job_type_hourly_rate', 13, 3, 0));
            }
        }
        $this->doneUsingTables();

        $this->execute(
            "UPDATE time_records AS tr
             LEFT JOIN projects AS pr ON pr.id = tr.parent_id
             LEFT JOIN job_types AS jt ON tr.job_type_id = jt.id
             LEFT JOIN custom_hourly_rates AS chr1 ON tr.job_type_id = chr1.job_type_id AND chr1.parent_type = 'Company' AND chr1.parent_id = pr.id
             LEFT join custom_hourly_rates AS chr2 ON tr.job_type_id = chr2.job_type_id AND chr2.parent_type = 'Project' AND chr2.parent_id = pr.id
            SET tr.job_type_hourly_rate = COALESCE(chr2.hourly_rate, chr1.hourly_rate, jt.default_hourly_rate, 0), tr.updated_on = UTC_TIMESTAMP()
            WHERE tr.parent_type = 'Project';
            "
        );

        $this->execute(
            "UPDATE time_records AS tr
             LEFT JOIN tasks AS t ON t.id = tr.parent_id
             LEFT JOIN job_types AS jt ON tr.job_type_id = jt.id
             LEFT JOIN custom_hourly_rates AS chr1 ON tr.job_type_id = chr1.job_type_id AND chr1.parent_type = 'Company' AND chr1.parent_id = t.project_id
             LEFT join custom_hourly_rates AS chr2 ON tr.job_type_id = chr2.job_type_id AND chr2.parent_type = 'Project' AND chr2.parent_id = t.project_id
            SET tr.job_type_hourly_rate = COALESCE(chr2.hourly_rate, chr1.hourly_rate, jt.default_hourly_rate, 0), tr.updated_on = UTC_TIMESTAMP()
            WHERE tr.parent_type = 'Task';
            "
        );
    }
}
