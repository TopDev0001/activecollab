<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Database query error.
 *
 * @package angie.library.database
 * @subpackage errors
 */
class DBQueryError extends Error
{
    /**
     * Construct the DBQueryError.
     *
     * @param  string       $sql
     * @param  int          $error_number
     * @param  string       $error_message
     * @param  string       $message
     * @return DBQueryError
     */
    public function __construct($sql, $error_number, $error_message, $message = null)
    {
        if ($message === null) {
            $message = "Query failed with message '$error_message' (SQL: $sql)";
        }

        parent::__construct($message, [
            'sql' => $sql,
            'error_number' => $error_number,
            'error_message' => $error_message,
        ]);
    }
}
