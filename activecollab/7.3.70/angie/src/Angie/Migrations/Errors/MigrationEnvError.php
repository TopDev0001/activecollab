<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Migrations\Errors;

use Angie\Error;

class MigrationEnvError extends Error
{
    /**
     * Construct error object.
     *
     * @param string $message
     */
    public function __construct($message = null)
    {
        if (empty($message)) {
            $message = 'Migration Environment Error';
        }

        parent::__construct($message);
    }
}
