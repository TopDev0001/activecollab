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
class DirectoryCreateError extends Error
{
    /**
     * Construct the DirectoryCreateError.
     *
     * @param string $path
     * @param string $message
     */
    public function __construct($path, $message = null)
    {
        if (is_null($message)) {
            $message = "Directory '$path' could not be created";
        }

        parent::__construct($message, ['path' => $path]);
    }
}
