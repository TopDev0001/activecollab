<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated_on field to job types model.
 *
 * @package ActiveCollab.migrations
 */
class MigrateUpdatedOnForJobTypes extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $job_types = $this->useTableForAlter('job_types');
        $job_types->addColumn(new DBUpdatedOnColumn());

        $this->execute('UPDATE ' . $job_types->getName() . ' SET updated_on = UTC_TIMESTAMP()');

        $this->doneUsingTables();
    }
}
