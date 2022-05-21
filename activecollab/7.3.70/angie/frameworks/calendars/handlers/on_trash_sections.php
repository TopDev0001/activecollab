<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Trash\Sections;

/**
 * on_trash_sections event handler.
 *
 * @package angie.frameworks.calendars
 * @subpackage handlers
 */

/**
 * Handle on_trash_sections event.
 */
function calendars_handle_on_trash_sections(Sections &$sections, User $user)
{
    if ($user->isOwner()) {
        // get calendars
        $calendars_id_name_map = DB::executeIdNameMap(
            'SELECT c.id, c.name FROM calendars AS c
                WHERE c.is_trashed = ?
                ORDER BY c.trashed_on DESC',
            true
        );

        // get events
        $events_id_name_map = DB::executeIdNameMap(
            'SELECT ce.id, ce.name FROM calendar_events AS ce
                INNER JOIN calendars AS c ON c.id = ce.calendar_id AND c.is_trashed = ?
                WHERE ce.is_trashed = ?
                ORDER BY ce.trashed_on DESC',
            false,
            true
        );
    } elseif ($user->isMember() && $calendar_ids = Calendars::findIdsByUser($user)) {
        // get calendars
        $calendars_id_name_map = DB::executeIdNameMap(
            'SELECT c.id, c.name FROM calendars AS c
                WHERE c.trashed_by_id = ? AND c.is_trashed = ?
                ORDER BY c.trashed_on DESC',
            $user->getId(),
            true
        );

        // get events
        $events_id_name_map = DB::executeIdNameMap(
            'SELECT ce.id, ce.name FROM calendar_events AS ce
                INNER JOIN calendars AS c ON c.id = ce.calendar_id AND c.is_trashed = ?
                WHERE ce.calendar_id IN (?) AND ce.trashed_by_id = ? AND ce.is_trashed = ?
                ORDER BY ce.trashed_on DESC',
            false,
            $calendar_ids,
            $user->getId(),
            true
        );
    } else {
        $calendars_id_name_map = null;
        $events_id_name_map = null;
    }

    $sections->registerTrashedObjects('Calendar', $calendars_id_name_map);
    $sections->registerTrashedObjects('CalendarEvent', $events_id_name_map, Sections::SECOND_WAVE);
}
