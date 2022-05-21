<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Directory could not be deleted.
 *
 * @package angie.library.errors
 */
class DirectoryDeleteError extends Error
{
    /**
     * Construct the DirectoryDeleteError.
     *
     * @param string $directory_path
     * @param string $message
     */
    public function __construct($directory_path, $message = null)
    {
        if (is_null($message)) {
            $message = "Directory '$directory_path' could not be deleted";
        }

        parent::__construct($message, [
            'directory' => $directory_path,
        ]);
    }
}
