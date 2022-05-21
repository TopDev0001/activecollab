<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Authentication\Adapter\AdapterInterface;
use ActiveCollab\Authentication\Authorizer\AuthorizerInterface;
use ActiveCollab\Authentication\LoginPolicy\LoginPolicyInterface;
use ActiveCollab\Authentication\Password\Manager\PasswordManagerInterface;
use ActiveCollab\Authentication\Password\Policy\PasswordPolicyInterface;

/**
 * Common authentication integration.
 */
interface AuthorizationIntegrationInterface
{
    /**
     * Return available authentication adapters.
     *
     * @return AdapterInterface[]
     */
    public function getAdapters();

    /**
     * @return AuthorizerInterface
     */
    public function getAuthorizer();

    /**
     * @return LoginPolicyInterface
     */
    public function getLoginPolicy();

    /**
     * @return PasswordPolicyInterface
     */
    public function getPasswordPolicy();

    /**
     * @return PasswordManagerInterface
     */
    public function getPasswordManager();

    /**
     * Return TRUE if this authorization integration support invitation of new owners using ActiveCollab interface.
     *
     * @return bool
     */
    public function canInviteOwners();

    /**
     * Return TRUE if this authorization integration support invitation of new members using ActiveCollab interface.
     *
     * @return bool
     */
    public function canInviteMembers();

    /**
     * Return TRUE if this authorization integration support invitation of new clients using ActiveCollab interface.
     *
     * @return bool
     */
    public function canInviteClients();

    /**
     * Return TRUE if this authorization integration support invitation of new users using ActiveCollab interface.
     *
     * @return bool
     */
    public function canInviteUsers();
}
