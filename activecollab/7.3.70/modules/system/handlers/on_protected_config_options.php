<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * on_protected_config_options event handler.
 *
 * @package ActiveCollab.modules.system
 * @subpackage handlers
 */

use Angie\Utils\FeatureStatusResolver\FeatureStatusResolverInterface;

/**
 * Handle on_protected_config_options event.
 */
function system_handle_on_protected_config_options()
{
    ConfigOptions::protect(['morning_paper_last_activity'], function () {
        return false;
    }, function () {
        return false;
    });

    // Password policy settings can be read by anyone, but managed by owners only.
    ConfigOptions::protect(['password_policy_min_length', 'password_policy_require_numbers', 'password_policy_require_mixed_case', 'password_policy_require_symbols', 'default_hide_from_clients'], function (User $user) {
        return true;
    }, function (User $user) {
        return $user->isOwner();
    });

    ConfigOptions::protect(['maintenance_enabled'], function (User $user) {
        return $user->isOwner();
    }, function (User $user) {
        return $user->isOwner();
    });

    ConfigOptions::protect(['workload_enabled', 'user_daily_capacity'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = AngieApplication::getContainer()
            ->get(FeatureStatusResolverInterface::class)
            ->isLocked(
                AngieApplication::featureFactory()->makeFeature('workload')
            );

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(['hide_from_clients_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = AngieApplication::getContainer()
            ->get(FeatureStatusResolverInterface::class)
            ->isLocked(
                AngieApplication::featureFactory()->makeFeature('hide_from_clients')
            );

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(['availability_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = AngieApplication::getContainer()
            ->get(FeatureStatusResolverInterface::class)
            ->isLocked(
                AngieApplication::featureFactory()->makeFeature('availability')
            );

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(
        ['calendar_enabled', 'calendar_enabled_lock', 'budgeting_enabled_lock', 'chat_enabled', 'chat_enabled_lock'],
        function (User $user) {
            return true;
        },
        function (User $user) {
            return false;
        }
    );

    ConfigOptions::protect(['budgeting_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = AngieApplication::getContainer()
            ->get(FeatureStatusResolverInterface::class)
            ->isLocked(
                AngieApplication::featureFactory()->makeFeature('budgeting')
            );

        return !$is_locked && $user->isOwner();
    });

    ConfigOptions::protect(['project_templates_enabled'], function (User $user) {
        return true;
    }, function (User $user) {
        $is_locked = ConfigOptions::getValue('project_templates_enabled_lock');

        return !$is_locked && $user->isOwner();
    });
}
