<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated on field to recurring profile table.
 *
 * @package activeCollab.modules.system
 */
class MigrateAddUpdatedOnFieldToRecurringProfileTable extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $recurring_profiles = $this->useTableForAlter('recurring_profiles');

        // add updated_on column
        $recurring_profiles->addColumn(new DBUpdatedOnColumn(), 'created_by_email');

        // set updated_on to current timestamp
        $this->execute("UPDATE {$recurring_profiles->getName()} SET updated_on = UTC_TIMESTAMP()");

        $this->doneUsingTables();
    }
}
