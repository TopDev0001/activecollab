<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * File create error implementation.
 *
 * @package angie.library.errors
 */
class FileCreateError extends Error
{
    /**
     * Construct the FileCreateError.
     *
     * @param string $file_path
     * @param string $message
     */
    public function __construct($file_path, $message = null)
    {
        if (is_null($message)) {
            $message = "File '$file_path' could not be created";
        }

        parent::__construct($message, [
            'file_path' => $file_path,
        ]);
    }
}
