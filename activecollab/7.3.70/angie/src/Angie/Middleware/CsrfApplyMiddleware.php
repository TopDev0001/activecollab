<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use ActiveCollab\Authentication\AuthenticationResult\Transport\Authorization\AuthorizationTransportInterface;
use ActiveCollab\Authentication\AuthenticationResult\Transport\CleanUp\CleanUpTransportInterface;
use ActiveCollab\Authentication\AuthenticationResult\Transport\Deauthentication\DeauthenticationTransportInterface;
use ActiveCollab\Authentication\AuthenticationResult\Transport\TransportInterface;
use Angie\Middleware\Base\CsrfMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserSession;

/**
 * @package Angie\Middleware
 */
class CsrfApplyMiddleware extends CsrfMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $action_result = $request->getAttribute(MiddlewareInterface::ACTION_RESULT_ATTRIBUTE);

        if ($action_result instanceof TransportInterface) {
            if ($action_result instanceof AuthorizationTransportInterface) {
                $authenticated_with = $action_result->getAuthenticatedWith();

                if ($authenticated_with instanceof UserSession) {
                    [$request, $response] = $this->cookies->set($request, $response, $this->getCsrfValidatorCookieName(), $authenticated_with->getCsrfValidator(), [
                        'ttl' => $authenticated_with->getSessionTtl(),
                        'http_only' => false,
                    ]);
                }
            } elseif ($action_result instanceof DeauthenticationTransportInterface || $action_result instanceof CleanUpTransportInterface) {
                [$request, $response] = $this->cookies->remove($request, $response, $this->getCsrfValidatorCookieName());
            }
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }
}
