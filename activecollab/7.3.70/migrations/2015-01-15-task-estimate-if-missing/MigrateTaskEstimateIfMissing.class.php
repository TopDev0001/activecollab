<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add estimate column if it is missing.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskEstimateIfMissing extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        if (!$tasks->getColumn('estimate')) {
            $tasks->addColumn(DBDecimalColumn::create('estimate', 12, 2, 0)->setUnsigned(true), 'job_type_id');
        }

        $this->doneUsingTables();
    }
}
