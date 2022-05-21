<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddFieldOriginalIsTrashedToCalendarEvents extends AngieModelMigration
{
    public function up()
    {
        $calendar_events = $this->useTableForAlter('calendar_events');

        $calendar_events->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );

        $this->execute('UPDATE ' . $calendar_events->getName() . ' SET original_is_trashed = ?', false);
    }
}
