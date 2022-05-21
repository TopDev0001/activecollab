<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * General database error.
 *
 * @package angie.library.database
 * @subpackage errors
 */
class DBError extends Error
{
    /**
     * Construct the DBQueryError.
     *
     * @param int    $error_number
     * @param string $error_message
     * @param string $message
     */
    public function __construct($error_number, $error_message, $message = null)
    {
        if ($message === null) {
            $message = "Problem with database interaction. Database said: '$error_message'";
        }

        parent::__construct($message, [
            'error_number' => $error_number,
            'error_message' => $error_message,
        ]);
    }
}
