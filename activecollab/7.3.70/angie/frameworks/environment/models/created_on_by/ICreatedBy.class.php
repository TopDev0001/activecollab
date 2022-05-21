<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Created by interface definition.
 *
 * @package angie.framework.environment
 * @subpackage models
 */
interface ICreatedBy
{
    /**
     * Return user who created this object.
     *
     * @return User|IUser|null
     */
    public function getCreatedBy();

    /**
     * Set object author.
     *
     * @param User|IUser|null $user
     */
    public function setCreatedBy($user);

    /**
     * Check if $user created this object.
     *
     * @return bool
     */
    public function isCreatedBy(IUser $user);

    /**
     * Return ID of user who created parent object.
     *
     * @return int
     */
    public function getCreatedById();
}
