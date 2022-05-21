<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;

AngieApplication::useController('fw_calendars', CalendarsFramework::NAME);

class CalendarsController extends FwCalendarsController
{
    public function all_calendar_events(Request $request, User $user)
    {
        return CalendarEvents::prepareCollection(
            sprintf('all_events_for_period_%s_%s', $request->get('from'), $request->get('to')),
            $user
        );
    }
}
