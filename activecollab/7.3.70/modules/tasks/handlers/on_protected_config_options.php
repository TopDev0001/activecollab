<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_protected_config_options event handler.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage handlers
 */

/**
 * Handle on_protected_config_options event.
 */
function tasks_handle_on_protected_config_options()
{
    ConfigOptions::protect(['show_project_id', 'show_task_id'], function (User $user) {
        return true;
    }, function (User $user) {
        return $user->isOwner();
    });

    ConfigOptions::protect(
        [
            'task_estimates_enabled_lock',
            'task_dependencies_enabled_lock',
            'auto_reschedule_enabled_lock',
            'timeline_enabled_lock',
        ],
        function (User $user) {
            return true;
        },
        function (User $user) {
            return false;
        }
    );

    ConfigOptions::protect(['task_estimates_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = ConfigOptions::getValue('task_estimates_enabled_lock');

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(['timeline_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = ConfigOptions::getValue('timeline_enabled_lock');

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(['task_dependencies_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = ConfigOptions::getValue('task_dependencies_enabled_lock');

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(['auto_reschedule_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = ConfigOptions::getValue('auto_reschedule_enabled_lock');

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(['recurring_tasks_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = ConfigOptions::getValue('recurring_tasks_enabled_lock');

        return !$is_locked && $user->isOwner();
    });
}
