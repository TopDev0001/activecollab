<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop parent field from calendar events model.
 *
 * @package angie.migrations
 */
class MigrateDropParentFromCalendarEventsModel extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $calendar_events_table = $this->useTableForAlter('calendar_events');

        $calendar_events_table->dropColumn('type');
        $calendar_events_table->dropColumn('parent_type');
        $calendar_events_table->alterColumn('parent_id', DBIntegerColumn::create('calendar_id', 10, 0)->setUnsigned(true));
    }
}
