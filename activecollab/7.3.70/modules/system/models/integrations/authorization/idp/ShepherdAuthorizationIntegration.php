<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Authentication\Password\Manager\PasswordManagerInterface;
use ActiveCollab\Authentication\Password\Policy\PasswordPolicy;
use ActiveCollab\Authentication\Saml\SamlUtils;
use Angie\Authentication\Policies\LoginPolicy;

class ShepherdAuthorizationIntegration extends IdpAuthorizationIntegration
{
    /**
     * @return string
     */
    private function getSamlAuthenticateUrl()
    {
        return defined('SHEPHERD_IDP_ENDPOINT')
            ? SHEPHERD_IDP_ENDPOINT
            : $this->getAdditionalProperty('authenticate_url');
    }

    /**
     * @return string
     */
    private function getSamlCertificate()
    {
        return defined('SHEPHERD_SAML_CRT')
            ? file_get_contents(SHEPHERD_SAML_CRT)
            : $this->getAdditionalProperty('saml_crt');
    }

    /**
     * @return string
     */
    private function getSamlKey()
    {
        return defined('SHEPHERD_SAML_KEY')
            ? file_get_contents(SHEPHERD_SAML_KEY)
            : $this->getAdditionalProperty('saml_key');
    }

    /**
     * @return string
     */
    private function getLogoutUrl()
    {
        $shepherd_url = defined('SHEPHERD_URL') ? SHEPHERD_URL : $this->getAdditionalProperty('shepherd_url');

        return "{$shepherd_url}/logout";
    }

    public function getLoginPolicy()
    {
        $saml = new SamlUtils();
        $authn_request = $saml->getAuthnRequest(
            $this->getConsumerServiceUrl(),
            $this->getSamlAuthenticateUrl(),
            htmlspecialchars($this->getIssuer()),
            $this->getSamlCertificate(),
            $this->getSamlKey()
        );

        return new LoginPolicy(
            LoginPolicy::USERNAME_FORMAT_TEXT,
            true,
            true,
            false,
            $authn_request,
            $this->getLogoutUrl(),
            null,
            null
        );
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
