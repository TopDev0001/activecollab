<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_resets_initial_settings_timestamp event handler.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * @param array $config_options
 */
function invoicing_handle_on_resets_initial_settings_timestamp(array &$config_options)
{
    $config_options[] = 'default_accounting_app';
}
