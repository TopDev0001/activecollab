<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce custom hourly rates table and migrate custom project hourly rates.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigrateCustomHourlyRatesForClients extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->createTable(DB::createTable('custom_hourly_rates')->addColumns([
            new DBParentColumn(true, false),
            DBIntegerColumn::create('job_type_id', DBColumn::NORMAL, 0)->setUnsigned(true),
            (new DBMoneyColumn('hourly_rate', 0))
                ->setUnsigned(true),
        ])->addIndices([
            new DBIndexPrimary(['parent_type', 'parent_id', 'job_type_id']),
        ]));

        if ($this->tableExists('project_hourly_rates')) {
            [$custom_hourly_rates, $projects, $project_hourly_rates] = $this->useTables('custom_hourly_rates', 'projects', 'project_hourly_rates');

            if ($rows = $this->execute("SELECT $project_hourly_rates.* FROM $project_hourly_rates LEFT JOIN $projects ON $projects.id = $project_hourly_rates.project_id")) {
                $batch = new DBBatchInsert($custom_hourly_rates, ['parent_type', 'parent_id', 'job_type_id', 'hourly_rate']);

                foreach ($rows as $row) {
                    $batch->insert('Project', $row['project_id'], $row['job_type_id'], $row['hourly_rate']);
                }

                $batch->done();
            }

            $this->dropTable('project_hourly_rates');
        }

        $this->doneUsingTables();
    }
}
