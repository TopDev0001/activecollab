<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Error that is throw when we try to change the role of the last owner.
 *
 * @package angie.frameworks.authentication
 * @subpackage models
 */
class LastOwnerRoleChangeError extends Error
{
    /**
     * Construct error instance.
     *
     * @param string $user
     * @param string $message
     */
    public function __construct($user, $message = null)
    {
        $user_id = $user instanceof User ? $user->getId() : $user;

        if (empty($message)) {
            $message = "Can't change role of user #{$user_id} because that is the last account with Owner role";
        }

        parent::__construct($message, ['user_id' => $user_id]);
    }
}
