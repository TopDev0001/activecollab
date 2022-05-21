<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddEndsOnTimeCalendarEvents extends AngieModelMigration
{
    public function up()
    {
        $events = $this->useTableForAlter('calendar_events');

        $events->addColumn(new DBTimeColumn('ends_on_time'), 'ends_on');
        $events->addIndex(DBIndex::create('ends_on_time'));

        $this->doneUsingTables();
    }
}
