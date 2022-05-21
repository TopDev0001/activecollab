<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Upgrade repeating calendar event fields.
 *
 * @package angie.migrations
 */
class MigrateRepeatingCalendarEventFieldsUpdate extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $calendar_events_table = $this->useTableForAlter('calendar_events');

        $calendar_events_table->dropColumn('repeat_event_option');
        $calendar_events_table->alterColumn('repeat_event', DBStringColumn::create('repeat_event', 150));
        $this->execute("UPDATE {$calendar_events_table->getName()} SET repeat_event = ? WHERE repeat_event = ?", null, 'dont');
    }
}
