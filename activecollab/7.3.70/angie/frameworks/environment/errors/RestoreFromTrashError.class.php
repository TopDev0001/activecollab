<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Throw restore from trash error.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
final class RestoreFromTrashError extends Error
{
    /**
     * Construct the new error message.
     *
     * @param string $message
     */
    public function __construct($message = '', $additional = null, $previous = null)
    {
        if (empty($message)) {
            $message = "Object can't be restored from trash";
        }

        parent::__construct($message, $additional, $previous);
    }
}
