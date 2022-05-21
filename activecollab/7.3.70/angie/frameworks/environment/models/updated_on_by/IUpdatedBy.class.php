<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update by interface definition.
 *
 * @package angie.framework.environment
 * @subpackage models
 */
interface IUpdatedBy
{
    /**
     * Return user who last updated this object.
     *
     * @return User|IUser|null
     */
    public function getUpdatedBy();

    /**
     * Set information about user who last updated this object.
     *
     * @param User|IUser|null $user
     */
    public function setUpdatedBy($user);

    /**
     * Return ID of user who updated parent object.
     *
     * @return int
     */
    public function getUpdatedById();
}
