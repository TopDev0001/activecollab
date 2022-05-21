<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie;

use ActiveCollab\Authentication\Adapter\AdapterInterface;
use ActiveCollab\Authentication\Adapter\BrowserSessionAdapter;
use ActiveCollab\Authentication\Adapter\TokenBearerAdapter;
use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\Authentication as BaseAuthentication;
use ActiveCollab\Authentication\AuthenticationResult\Transport\TransportInterface;
use ActiveCollab\Authentication\Authorizer\AuthorizerInterface;
use ActiveCollab\Authentication\LoginPolicy\LoginPolicyInterface;
use ActiveCollab\Authentication\Password\Manager\PasswordManagerInterface;
use ActiveCollab\Authentication\Password\Policy\PasswordPolicyInterface;
use ActiveCollab\Authentication\Password\StrengthValidator\PasswordStrengthValidator;
use ApiSubscription;
use AuthorizationIntegrationInterface;
use InvalidArgumentException;
use RuntimeException;
use User;

class Authentication extends BaseAuthentication
{
    private AuthorizationIntegrationInterface $authorization_integration;

    /**
     * Password policy instance.
     *
     * @var PasswordPolicyInterface
     */
    private $password_policy = false;

    /**
     * Authentication's login policy.
     *
     * @var array
     */
    private $login_policy = false;

    public function __construct(AuthorizationIntegrationInterface $authorization_integration)
    {
        parent::__construct(...$authorization_integration->getAdapters());

        $this->authorization_integration = $authorization_integration;
    }

    /**
     * Return authorizer from authorization integration.
     *
     * @return AuthorizerInterface
     */
    public function getAuthorizer()
    {
        return $this->authorization_integration->getAuthorizer();
    }

    public function authenticate(array $credentials)
    {
    }

    /**
     * @param  string             $adapter_class
     * @param  mixed              $payload
     * @return TransportInterface
     */
    public function authorizeForAdapter(array $credentials, $adapter_class, $payload = null)
    {
        $adapter = $this->getAdapterByClass($adapter_class);

        if (!$adapter instanceof AdapterInterface) {
            throw new InvalidArgumentException('Adapter not found');
        }

        $result = $this->authorize($this->authorization_integration->getAuthorizer(), $adapter, $credentials, $payload);

        if (empty($payload)) {
            if ($adapter_class === BrowserSessionAdapter::class) {
                $result->setPayload(\Users::prepareCollection('initial_for_logged_user', $result->getAuthenticatedUser())); // @TODO Move to be a dependency
            } elseif ($adapter_class === TokenBearerAdapter::class) {
                $token = $result->getAuthenticatedWith();

                if ($token instanceof ApiSubscription) {
                    $result->setPayload([
                        'is_ok' => true,
                        'token' => $result->getAuthenticatedUser()->getId() . '-' . $token->getTokenId(),
                    ]);
                }
            }
        }

        $this->setAuthenticatedUser($result->getAuthenticatedUser());
        $this->setAuthenticatedWith($result->getAuthenticatedWith());

        return $result;
    }

    // ---------------------------------------------------
    //  Settings
    // ---------------------------------------------------

    /**
     * Return login policy.
     *
     * @return LoginPolicyInterface
     */
    public function getLoginPolicy()
    {
        return $this->login_policy = $this->authorization_integration->getLoginPolicy();
    }

    /**
     * Password manager instance.
     *
     * @var PasswordManagerInterface
     */
    private $password_manager;

    /**
     * Return password manager instance.
     *
     * @return PasswordManagerInterface
     */
    public function getPasswordManager()
    {
        if (!empty($this->password_manager)) {
            return $this->password_manager;
        }

        return $this->authorization_integration->getPasswordManager();
    }

    /**
     * Set password manager.
     *
     * @return $this
     */
    public function &setPasswordManager(PasswordManagerInterface $password_manager = null)
    {
        $this->password_manager = $password_manager;

        return $this;
    }

    /**
     * Return password policy instance.
     *
     * @return PasswordPolicyInterface
     */
    public function getPasswordPolicy()
    {
        if (!empty($this->password_policy)) {
            return $this->password_policy;
        }

        return $this->authorization_integration->getPasswordPolicy();
    }

    /**
     * Set password policy.
     *
     * @return $this
     */
    public function &setPasswordPolicy(PasswordPolicyInterface $password_policy = null)
    {
        $this->password_policy = $password_policy;

        return $this;
    }

    /**
     * Return true if password is valid and matches requirements set by the password policy.
     *
     * @param  string $password
     * @return bool
     */
    public function validatePasswordStrength($password)
    {
        return (new PasswordStrengthValidator())->validate($password, $this->getPasswordPolicy());
    }

    /**
     * Return a strong password.
     *
     * @param  int    $length
     * @return string
     */
    public function generateStrongPassword($length)
    {
        return (new PasswordStrengthValidator())->generateValid($length, $this->getPasswordPolicy());
    }

    /**
     * Return logged in user.
     *
     * @return AuthenticatedUserInterface|User
     */
    public function getLoggedUser()
    {
        return $this->getAuthenticatedUser();
    }

    /**
     * Return logged user ID.
     *
     * @return int
     */
    public function getLoggedUserId()
    {
        return $this->getAuthenticatedUser() ? $this->getAuthenticatedUser()->getId() : 0;
    }

    /**
     * Return adapter by class.
     *
     * @param  string           $adapter_class
     * @return AdapterInterface
     */
    public function getAdapterByClass($adapter_class)
    {
        foreach ($this->getAdapters() as $adapter) {
            if ($adapter instanceof $adapter_class) {
                return $adapter;
            }
        }

        throw new RuntimeException('Requested adapter not found');
    }
}
