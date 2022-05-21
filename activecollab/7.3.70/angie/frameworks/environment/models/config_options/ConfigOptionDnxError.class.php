<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Unknown configuration option definition.
 *
 * @package angie.library.config_options
 * @subpackage errors
 */
class ConfigOptionDnxError extends Error
{
    /**
     * Thrown when $name configuration option does not exist.
     *
     * @param  string               $name
     * @param  string               $message
     * @return ConfigOptionDnxError
     */
    public function __construct($name, $message = null)
    {
        if (empty($message)) {
            $message = "Configuration option '$name' does not exist";
        }

        parent::__construct($message, ['option_name' => $name]);
    }
}
