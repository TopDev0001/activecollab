<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_resets_initial_settings_timestamp event handler.
 *
 * @package angie.frameworks.environment
 * @subpackage handlers
 */

/**
 * @param array $config_options
 */
function environment_handle_on_resets_initial_settings_timestamp(array &$config_options)
{
    $config_options[] = 'identity_name';
}
