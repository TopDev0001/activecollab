<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\SecurityLog\EventHandlers;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\AuthenticatedUser\RepositoryInterface;
use ActiveCollab\Authentication\AuthenticationResult\AuthenticationResultInterface;
use Angie\Authentication\SecurityLog\SecurityLogInterface;

/**
 * @package Angie\Authentication\SecurityLog\EventHandlers
 */
class DeauthenticationEventHander extends EventHander
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

    public function __invoke(AuthenticationResultInterface $authenticated_with)
    {
        $user = $authenticated_with->getAuthenticatedUser($this->users_repository);

        if ($user instanceof AuthenticatedUserInterface) {
            $this->getSecurityLog()->recordLogout($user);
        }
    }
}
