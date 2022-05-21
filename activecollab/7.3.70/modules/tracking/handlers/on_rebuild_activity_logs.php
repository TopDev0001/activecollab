<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Tracking module on_rebuild_activity_logs event handler implementation.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage handlers
 */

/**
 * @param Angie\NamedList $actions
 */
function tracking_handle_on_rebuild_activity_logs(&$actions)
{
    $actions->add('rebuild_tracking', [
        'label' => 'Rebuild time and expense log entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "TimeRecord" AS parent_type, r.id AS "parent_id", CONCAT("projects/", p.id, "/visible-to-clients/time-records/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM time_records as r LEFT JOIN projects as p ON r.parent_id = p.id WHERE r.parent_type = ? AND p.is_client_reporting_enabled = ?', 'Project', true);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "TimeRecord" AS parent_type, r.id AS "parent_id", CONCAT("projects/", p.id, "/hidden-from-clients/time-records/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM time_records as r LEFT JOIN projects as p ON r.parent_id = p.id WHERE r.parent_type = ? AND p.is_client_reporting_enabled = ?', 'Project', false);

            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "Expense" AS parent_type, r.id AS "parent_id", CONCAT("projects/", p.id, "/visible-to-clients/expenses/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM expenses as r LEFT JOIN projects as p ON r.parent_id = p.id WHERE r.parent_type = ? AND p.is_client_reporting_enabled = ?', 'Project', true);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "Expense" AS parent_type, r.id AS "parent_id", CONCAT("projects/", p.id, "/hidden-from-clients/expenses/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM expenses as r LEFT JOIN projects as p ON r.parent_id = p.id WHERE r.parent_type = ? AND p.is_client_reporting_enabled = ?', 'Project', false);

            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "TimeRecord" AS parent_type, r.id AS "parent_id", CONCAT("projects/", t.project_id, "/visible-to-clients/time-records/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM time_records AS r LEFT JOIN tasks AS t ON r.parent_id = t.id WHERE r.parent_type = ? AND t.is_hidden_from_clients = ?', 'Task', false);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "TimeRecord" AS parent_type, r.id AS "parent_id", CONCAT("projects/", t.project_id, "/hidden-from-clients/time-records/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM time_records AS r LEFT JOIN tasks AS t ON r.parent_id = t.id WHERE r.parent_type = ? AND t.is_hidden_from_clients = ?', 'Task', true);

            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "Expense" AS parent_type, r.id AS "parent_id", CONCAT("projects/", t.project_id, "/visible-to-clients/expenses/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM expenses AS r LEFT JOIN tasks AS t ON r.parent_id = t.id WHERE r.parent_type = ? AND t.is_hidden_from_clients = ?', 'Task', false);
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "TrackingObjectCreatedActivityLog" AS "type", "Expense" AS parent_type, r.id AS "parent_id", CONCAT("projects/", t.project_id, "/hidden-from-clients/expenses/", r.id) AS "parent_path", r.created_on, r.created_by_id, r.created_by_name, r.created_by_email, "" AS "raw_additional_properties" FROM expenses AS r LEFT JOIN tasks AS t ON r.parent_id = t.id WHERE r.parent_type = ? AND t.is_hidden_from_clients = ?', 'Task', true);
        },
    ]);

    $actions->add('rebuild_time_record_modifications', [
        'label' => 'Rebuild time record update entries',
        'callback' => ['TimeRecords', 'rebuildUpdateActivites'],
    ]);

    $actions->add('rebuild_expense_modifications', [
        'label' => 'Rebuild expense update entries',
        'callback' => ['Expenses', 'rebuildUpdateActivites'],
    ]);
}
