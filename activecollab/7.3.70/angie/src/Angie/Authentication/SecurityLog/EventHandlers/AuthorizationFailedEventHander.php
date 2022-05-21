<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\SecurityLog\EventHandlers;

use ActiveCollab\Authentication\AuthenticatedUser\RepositoryInterface;
use Angie\Authentication\SecurityLog\SecurityLogInterface;

/**
 * @package Angie\Authentication\SecurityLog\EventHandlers
 */
class AuthorizationFailedEventHander extends EventHander
{
    /**
     * @var RepositoryInterface
     */
    private $users_repository;

    /**
     * AuthorizationFailedEventHander constructor.
     */
    public function __construct(SecurityLogInterface $security_log, RepositoryInterface $users_repository)
    {
        parent::__construct($security_log);

        $this->users_repository = $users_repository;
    }

    public function __invoke(array $credentials)
    {
        $user = null;

        if (!empty($credentials['username'])) {
            $user = $this->users_repository->findByUsername($credentials['username']);
        }

        $this->getSecurityLog()->recordLoginAttempt($user);
    }
}
