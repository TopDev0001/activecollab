<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddNoteFieldToCalendarEvents extends AngieModelMigration
{
    public function up()
    {
        $calendar_events = $this->useTableForAlter('calendar_events');

        $calendar_events->addColumn(
            (new DBTextColumn('note'))
                ->setSize(DBTextColumn::BIG),
            'created_by_email'
        );
    }
}
