<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Migrations\Errors;

use Angie\Error;

/**
 * Migration does not exist error.
 *
 * @package angie.library.errors
 */
class MigrationDnxError extends Error
{
    /**
     * Construct error object.
     *
     * @param string $migration_name
     * @param string $changeset_name
     * @param string $message
     */
    public function __construct($migration_name, $changeset_name, $message = null)
    {
        if (empty($message)) {
            $message = "Migration '$migration_name' not found in '$changeset_name' change-set";
        }

        parent::__construct($message, [
            'migration_name' => $migration_name,
            'changeset_name' => $changeset_name,
        ]);
    }
}
