<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

class InvalidParamError extends Error
{
    /**
     * Construct the InvalidParamError.
     *
     * @param string      $var_name
     * @param mixed       $var_value
     * @param string|null $message
     */
    public function __construct($var_name, $var_value, $message = null)
    {
        if ($message === null) {
            $message = "$$var_name is not valid param value";
        }

        parent::__construct(
            $message,
            [
                'var_name' => $var_name,
                'var_value' => $var_value,
            ]
        );
    }
}
