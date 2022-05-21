<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * JSON error.
 *
 * This error is throw when Services_JSON fails to encode specific value to
 * JSON string
 *
 * @package angie.library.errors
 */
class JSONEncodeError extends Error
{
    /**
     * Construct JSON error instance.
     *
     * @param mixed  $var
     * @param string $message
     */
    public function __construct($var, $message = null)
    {
        if ($message === null) {
            $message = 'Failed to encode specified value to JSON string';
        }

        parent::__construct($message, [
            'value' => $var,
        ]);
    }
}
