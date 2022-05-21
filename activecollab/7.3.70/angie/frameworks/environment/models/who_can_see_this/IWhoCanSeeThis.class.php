<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Basic who can see this interface.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
interface IWhoCanSeeThis
{
    /**
     * Check if given user can see this object.
     *
     * @return bool
     */
    public function canUserSeeThis(User $user);

    /**
     * Return list of user ids available to see this object.
     *
     * @return array
     */
    public function whoCanSeeThis();
}
