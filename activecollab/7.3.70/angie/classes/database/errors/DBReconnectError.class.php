<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Reconnection error.
 *
 * @package angie.library.database
 * @subpackage errors
 */
class DBReconnectError extends Error
{
    /**
     * Construct reconnection error.
     *
     * @param string $message
     */
    public function __construct($message = null)
    {
        if (empty($message)) {
            $message = "Can't reconnect";
        }

        parent::__construct($message);
    }
}
