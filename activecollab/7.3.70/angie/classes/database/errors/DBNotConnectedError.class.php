<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Not connected to database error.
 *
 * @package angie.library.database
 * @subpackage errors
 */
class DBNotConnectedError extends Error
{
    /**
     * Construct not connected error.
     *
     * @param string $message
     */
    public function __construct($message = null)
    {
        if (empty($message)) {
            $message = 'Not connected to database';
        }

        parent::__construct($message);
    }
}
