<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_rebuild_activity_logs event handler implementation.
 *
 * @package angie.frameworks.calendars
 * @subpackage handlers
 */

/**
 * @param Angie\NamedList $actions
 */
function calendars_handle_on_rebuild_activity_logs(&$actions)
{
    $actions->add('rebuild_calendars', [
        'label' => 'Rebuild calendar entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Calendar" AS parent_type, id AS "parent_id", CONCAT("calendars/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM calendars');
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "CalendarEvent" AS parent_type, id AS "parent_id", CONCAT("calendars/", calendar_id, "/events/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM calendar_events');
        },
    ]);
}
