<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Database connection error.
 *
 * @package angie.library.database
 * @subpackage errors
 */
class DBConnectError extends Error
{
    /**
     * Construct the DBConnectError.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $database
     * @param string $message
     */
    public function __construct($host, $user, $pass, $database, $message = null)
    {
        if (is_null($message)) {
            $message = 'Failed to connect to database';
        }

        parent::__construct($message, [
            'host' => $host,
            'user' => $user,
            'password' => $pass ? make_string(strlen($pass), '*') : '',
            'database_name' => $database,
        ]);
    }
}
