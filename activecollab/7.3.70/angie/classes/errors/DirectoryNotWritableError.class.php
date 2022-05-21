<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Directory could not be created.
 *
 * @package angie.library.errors
 */
class DirectoryNotWritableError extends Error
{
    /**
     * Construct the DirectoryNotWritableError.
     *
     * @param string $directory_path
     * @param string $message
     */
    public function __construct($directory_path, $message = null)
    {
        if (is_null($message)) {
            $message = "Directory '$directory_path' is not writable";
        }

        parent::__construct($message, ['directory' => $directory_path]);
    }
}
