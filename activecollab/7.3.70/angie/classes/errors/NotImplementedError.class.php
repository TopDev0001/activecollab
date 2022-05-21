<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Not implemented error.
 *
 * @package angie.library.errors
 */
class NotImplementedError extends Error
{
    /**
     * Constructor.
     *
     * @param  string              $method
     * @param  string              $message
     * @return NotImplementedError
     */
    public function __construct($method, $message = null)
    {
        if ($message === null) {
            $message = "You are trying to use a method that is not implemented - $method()";
        }

        parent::__construct($message, [
            'method' => $method,
        ]);
    }
}
