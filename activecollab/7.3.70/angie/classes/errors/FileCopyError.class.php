<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * File copy error.
 *
 * @package angie.library.errors
 */
class FileCopyError extends Error
{
    /**
     * Construct the FileDnxError.
     *
     * @param string $from
     * @param string $to
     * @param string $message
     */
    public function __construct($from, $to, $message = null)
    {
        if (is_null($message)) {
            $message = "Failed to copy file from '$from' to '$to'";
        }

        parent::__construct($message, [
            'from' => $from,
            'to' => $to,
        ]);
    }
}
