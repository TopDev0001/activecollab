<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddSourceFieldToTimeRecords extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('time_records')) {
            $time_records = $this->useTableForAlter('time_records');

            if (!$time_records->getColumn('source')) {
                $time_records->addColumn(
                    new DBEnumColumn(
                    'source',
                        [
                            'timer_app',
                            'built_in_timer',
                            'my_time',
                            'my_timesheet',
                            'task_sidebar',
                            'project_time',
                            'project_timesheet',
                            'api_consumer',
                            'unknown',
                        ],
                        'unknown'
                    )
                );
            }

            $this->doneUsingTables();
        }
    }
}
