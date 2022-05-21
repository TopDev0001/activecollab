<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_clear_cache event handler.
 *
 * @package angie.frameworks.environment
 * @subpackage handlers
 */

/**
 * @param array $config_options
 */
function environment_handle_on_clear_cache(array &$config_options)
{
    $config_options[] = 'time_first_week_day';
    $config_options[] = 'time_workdays';
}
