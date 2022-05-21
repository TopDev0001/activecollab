<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_resets_initial_settings_timestamp event handler.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage handlers
 */
function tasks_handle_on_resets_initial_settings_timestamp(array &$config_options)
{
    $config_options[] = 'show_project_id';
    $config_options[] = 'show_task_id';
    $config_options[] = 'timeline_enabled';
    $config_options[] = 'timeline_enabled_lock';
    $config_options[] = 'task_estimates_enabled';
    $config_options[] = 'task_estimates_enabled_lock';
    $config_options[] = 'task_dependencies_enabled';
    $config_options[] = 'task_dependencies_enabled_lock';
    $config_options[] = 'auto_reschedule_enabled';
    $config_options[] = 'auto_reschedule_enabled_lock';
    $config_options[] = 'recurring_tasks_enabled';
    $config_options[] = 'recurring_tasks_enabled_lock';
}
