<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateDropCalendarEventState extends AngieModelMigration
{
    public function up()
    {
        $calendar_events = $this->useTableForAlter('calendar_events');

        $calendar_events->addColumn(
            new DBBoolColumn('is_trashed'),
            'created_by_email'
        );
        $calendar_events->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $calendar_events->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $calendar_events->addIndex(DBIndex::create('trashed_by_id'));

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $calendar_events->getName() . ' SET is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, STATE_TRASHED);

        $calendar_events->dropColumn('state');
        $calendar_events->dropColumn('original_state');
    }
}
