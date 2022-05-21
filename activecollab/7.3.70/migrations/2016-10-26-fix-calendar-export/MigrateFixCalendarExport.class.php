<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateFixCalendarExport extends AngieModelMigration
{
    public function up()
    {
        $dont_repeat = !empty(CalendarEvent::DONT_REPEAT) ? CalendarEvent::DONT_REPEAT : 'dont';
        $available_repeat_values = [$dont_repeat, CalendarEvent::REPEAT_DAILY, CalendarEvent::REPEAT_WEEKLY, CalendarEvent::REPEAT_MONTHLY, CalendarEvent::REPEAT_YEARLY];

        $this->execute('UPDATE calendar_events SET repeat_event = ?, repeat_until = ? WHERE repeat_event IS NULL OR repeat_event NOT IN (?)', $dont_repeat, null, $available_repeat_values);

        $calendar_events = $this->useTableForAlter('calendar_events');
        $calendar_events->alterColumn(
            'repeat_event',
            new DBEnumColumn('repeat_event', $available_repeat_values, $dont_repeat)
        );
        $calendar_events->addColumn(new DBUpdatedOnColumn(), 'created_by_email');

        $this->execute('UPDATE calendar_events SET updated_on = UTC_TIMESTAMP()');
    }
}
