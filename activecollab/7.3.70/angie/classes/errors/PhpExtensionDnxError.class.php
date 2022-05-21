<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Error that's thrown when required PHP extension is not loaded
 * Enter description here ...
 *
 * @package angie
 * @subpackage errors
 */
class PhpExtensionDnxError extends Error
{
    /**
     * Construct error instance.
     *
     * @param string $extension
     * @param string $message
     */
    public function __construct($extension, $message = null)
    {
        if (empty($message)) {
            $message = "'$extension' not loaded";
        }

        parent::__construct($message, [
            'extension' => $extension,
        ]);
    }
}
