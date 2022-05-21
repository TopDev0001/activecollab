<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJwt\Verifier\JwtVerifierInterface;
use ActiveCollab\Authentication\Authorizer\SamlAuthorizer;
use ActiveCollab\Authentication\Saml\SamlUtils;
use Angie\Authentication\ExceptionHandler\SamlExceptionHandler;
use Angie\Authentication\Repositories\UsersRepository;
use Angie\Authentication\RequestProcessor\ShepherdRequestProcessor;

abstract class IdpAuthorizationIntegration extends AuthorizationIntegration
{
    public function getAuthorizer()
    {
        return new SamlAuthorizer(
            new UsersRepository(),
            new ShepherdRequestProcessor(
                AngieApplication::currentTimestamp(),
                AngieApplication::getContainer()->get(JwtVerifierInterface::class),
                new SamlUtils()
            ),
            new SamlExceptionHandler()
        );
    }

    public function getConsumerServiceUrl(): string
    {
        $query_string = $this->getNewFrontendUrl()
            && str_starts_with(
                $this->getReferrer(),
                $this->getNewFrontendUrl()
            ) ? '?new-frontend=true' : '';

        return ROOT_URL . "/api/v1/user-session{$query_string}";
    }

    public function getIssuer(): string
    {
        return str_starts_with($this->getReferrer(), ROOT_URL) ? $this->getReferrer() : ROOT_URL;
    }

    protected function getReferrer()
    {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    protected function getNewFrontendUrl(): ?string
    {
        return defined('NEW_FRONTEND_URL') ? NEW_FRONTEND_URL : null;
    }
}
