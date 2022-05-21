<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated_on column to data filters table.
 *
 * @package angie.migrations
 */
class MigrateDataFiltersTableAddUpdatedOnField extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $data_filters = $this->useTableForAlter('data_filters');

        $data_filters->addColumn(new DBUpdatedOnColumn(), 'created_by_email');
        $this->execute("UPDATE {$data_filters->getName()} SET updated_on = UTC_TIMESTAMP()");

        $this->doneUsingTables();
    }
}
