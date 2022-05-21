<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateDropCalendarState extends AngieModelMigration
{
    public function up()
    {
        $calendars = $this->useTableForAlter('calendars');

        $calendars->addColumn(
            new DBBoolColumn('is_trashed'),
            'created_by_email'
        );
        $calendars->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $calendars->addColumn(
            DBFkColumn::create('trashed_by_id'
            ),
            'trashed_on'
        );
        $calendars->addIndex(DBIndex::create('trashed_by_id'));

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $calendars->getName() . ' SET is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, STATE_TRASHED);

        $calendars->dropColumn('state');
        $calendars->dropColumn('original_state');
    }
}
