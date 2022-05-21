<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Insufficient permissions error.
 *
 * @package angie.library.errors
 */
class InsufficientPermissionsError extends Error
{
    /**
     * Construct the InsufficientPermissionsError.
     *
     * @param string $message
     */
    public function __construct($message = null)
    {
        if ($message === null) {
            $message = 'You have Insufficient permissions for this action';
        }

        parent::__construct($message);
    }
}
