<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_resets_initial_settings_timestamp event handler.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage handlers
 */
function tracking_handle_on_resets_initial_settings_timestamp(array &$config_options)
{
    $config_options[] = 'invoice_second_tax_is_compound';
    $config_options[] = 'invoice_second_tax_is_enabled';
    $config_options[] = 'default_is_tracking_enabled';
    $config_options[] = 'default_is_client_reporting_enabled';
    $config_options[] = 'default_project_budget_type';
    $config_options[] = 'default_tracking_objects_are_billable';
    $config_options[] = 'default_members_can_change_billable';
    $config_options[] = 'default_budget_thresholds';
    $config_options[] = 'task_time_tracking_enabled';
    $config_options[] = 'expense_tracking_enabled';
}
