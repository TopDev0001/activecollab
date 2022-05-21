<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Rename is_active to is_archived for job types.
 *
 * @package ActiveCollab.migrations
 */
class MigrateIsArchivedForJobTypes extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $job_types = $this->useTableForAlter('job_types');

        $job_types->addColumn(new DBArchiveColumn(), 'is_active');
        $this->execute('UPDATE ' . $job_types->getName() . ' SET is_archived = ? WHERE is_active = ?', true, false);
        $job_types->dropColumn('is_active');

        $this->doneUsingTables();
    }
}
