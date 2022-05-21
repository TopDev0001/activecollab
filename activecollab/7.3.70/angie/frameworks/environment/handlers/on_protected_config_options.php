<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_protected_config_options event handler.
 *
 * @package angie.frameworks.environment
 * @subpackage handlers
 */

/**
 * Handle on_protected_config_options.
 */
function environment_handle_on_protected_config_options()
{
    // Editable by tech admins
    ConfigOptions::protect(['maintenance_message', 'help_improve_application', 'identity_name'], function (User $user) {
        return $user->isOwner();
    }, function (User $user) {
        return $user->isOwner();
    });

    // Hidden
    ConfigOptions::protect(['require_index_rebuild', 'whitelisted_tags', 'firewall_enabled', 'firewall_white_list', 'firewall_black_list', 'brute_force_protection_enabled', 'brute_force_cooldown_lenght', 'brute_force_cooldown_threshold'], function () {
        return false;
    }, function () {
        return false;
    });

    // Access, but edit only tech admin
    ConfigOptions::protect(['time_workdays'], function () {
        return true;
    }, function (User $user) {
        return $user->isOwner();
    });
}
