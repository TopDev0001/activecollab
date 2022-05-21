<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated on column to activity logs tables.
 *
 * @package angie.migrations
 */
class MigrateUpdatedOnForActivityLogs extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->useTableForAlter('activity_logs')->addColumn(new DBUpdatedOnColumn(), 'created_by_email');
        $this->doneUsingTables();
    }
}
