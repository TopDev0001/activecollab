<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * File delete error.
 *
 * @package angie.library.errors
 */
class FileDeleteError extends Error
{
    /**
     * Construct the FileDeleteError.
     *
     * @param mixed  $file
     * @param string $message
     */
    public function __construct($file, $message = null)
    {
        if (is_null($message)) {
            if (is_foreachable($file)) {
                $message = 'Failed to delete following files: ' . implode(', ', $file);
            } else {
                $message = "Failed to delete following file: {$file}";
            }
        }

        parent::__construct($message, [
            'files' => $file,
        ]);
    }
}
