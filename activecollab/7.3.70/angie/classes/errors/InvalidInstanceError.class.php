<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Invalid controller action error.
 *
 * @package angie.library.errors
 */
class InvalidInstanceError extends Error
{
    /**
     * Construct the InvalidInstanceError.
     *
     * @param string          $var_name
     * @param mixed           $var_value
     * @param string[]|string $expected_class
     * @param string          $message
     */
    public function __construct($var_name, $var_value, $expected_class, $message = null)
    {
        if ($message === null) {
            if (is_array($expected_class)) {
                $message = "$$var_name is expected to be an instance of one of the following classes: " . implode(', ', $expected_class);
            } else {
                $message = "$$var_name is expected to be an instance of $expected_class class";
            }
        }

        parent::__construct(
            $message,
            [
                'var_name' => $var_name,
                'var_value' => $var_value,
                'expected_class' => $expected_class,
            ]
        );
    }
}
