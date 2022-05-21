<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\Repositories;

use ActiveCollab\Authentication\AuthenticatedUser\RepositoryInterface;
use DataObjectPool;
use User;
use Users;

/**
 * @package Angie\Authentication\Repositories
 */
class UsersRepository implements RepositoryInterface
{
    public function findById($user_id)
    {
        return DataObjectPool::get(User::class, $user_id);
    }

    public function findByUsername($username)
    {
        return Users::findByEmail($username);
    }
}
