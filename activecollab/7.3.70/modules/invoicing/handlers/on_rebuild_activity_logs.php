<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Invoicing module on_rebuild_activity_logs event handler implementation.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * @param Angie\NamedList $actions
 */
function invoicing_handle_on_rebuild_activity_logs(&$actions)
{
    $actions->add('rebuild_invoicing', [
        'label' => 'Rebuild invoicing log entries',
        'callback' => function () {
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Invoice" AS parent_type, id AS "parent_id", CONCAT("invoices/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM invoices');
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "Estimate" AS parent_type, id AS "parent_id", CONCAT("estimates/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM estimates');
            DB::execute('INSERT INTO activity_logs (type, parent_type, parent_id, parent_path, created_on, created_by_id, created_by_name, created_by_email, raw_additional_properties) SELECT "InstanceCreatedActivityLog" AS "type", "RecurringProfile" AS parent_type, id AS "parent_id", CONCAT("recurring-profiles/", id) AS "parent_path", created_on, created_by_id, created_by_name, created_by_email, "" AS "raw_additional_properties" FROM recurring_profiles');
        },
    ]);

    $actions->add('rebuild_estimate_comments', [
        'label' => 'Rebuild estimate comment log entries',
        'callback' => function () {
            Comments::rebuildCommentCreatedParentPathForParentType('Estimate');
        },
    ]);
}
