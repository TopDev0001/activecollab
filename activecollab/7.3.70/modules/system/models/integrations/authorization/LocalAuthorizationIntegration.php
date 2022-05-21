<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Authentication\Authorizer\AuthorizerInterface;
use ActiveCollab\Authentication\Authorizer\LocalAuthorizer;
use ActiveCollab\Authentication\Password\Manager\PasswordManagerInterface;
use Angie\Authentication\Policies\LoginPolicy;
use Angie\Authentication\Policies\PasswordPolicy;
use Angie\Authentication\Repositories\UsersRepository;

class LocalAuthorizationIntegration extends AuthorizationIntegration
{
    public function getAuthorizer()
    {
        return new LocalAuthorizer(new UsersRepository(), AuthorizerInterface::USERNAME_FORMAT_EMAIL);
    }

    public function getLoginPolicy()
    {
        return new LoginPolicy(LoginPolicy::USERNAME_FORMAT_EMAIL);
    }

    public function getPasswordPolicy()
    {
        return new PasswordPolicy();
    }

    public function getPasswordManager()
    {
        return AngieApplication::getContainer()->get(PasswordManagerInterface::class);
    }

    public function canInviteOwners()
    {
        return true;
    }

    public function canInviteMembers()
    {
        return true;
    }

    public function canInviteClients()
    {
        return true;
    }
}
