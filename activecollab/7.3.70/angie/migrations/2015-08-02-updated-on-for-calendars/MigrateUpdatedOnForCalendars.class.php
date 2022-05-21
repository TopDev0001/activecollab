<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated on column to calendars model.
 *
 * @package angie.migrations
 */
class MigrateUpdatedOnForCalendars extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $calendars = $this->useTableForAlter('calendars');

        $calendars->addColumn(new DBUpdatedOnColumn(), 'created_by_email');
        $this->execute('UPDATE ' . $calendars->getName() . ' SET updated_on = created_on');

        $this->doneUsingTables();
    }
}
