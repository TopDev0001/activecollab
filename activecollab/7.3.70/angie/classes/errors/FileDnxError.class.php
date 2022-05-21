<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * File does not exist exception.
 *
 * @package angie.library.errors
 */
class FileDnxError extends Error
{
    /**
     * Construct the FileDnxError.
     *
     * @param string $file_path
     * @param string $message
     */
    public function __construct($file_path, $message = null)
    {
        if (is_null($message)) {
            $message = "File '$file_path' doesn't exists";
        }

        parent::__construct($message, ['path' => $file_path]);
    }
}
