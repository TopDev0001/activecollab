<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Middleware;

use ActiveCollab\Logger\AppRequest\HttpRequest;
use ActiveCollab\Logger\LoggerInterface;
use Angie\Middleware\Base\Middleware;
use ApiSubscription;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use User;
use UserSession;

class LogAuthenticationDataMiddleware extends Middleware
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    )
    {
        $logger = $this->getLogger();

        if ($logger instanceof LoggerInterface) {
            $authenticated_user = $request->getAttribute('authenticated_user');

            if ($authenticated_user instanceof User) {
                $request = $request
                    ->withAttribute('user_id', $authenticated_user->getId());
            }

            $authenticated_with = $request->getAttribute('authenticated_with');

            if ($authenticated_with instanceof UserSession) {
                $request = $request
                    ->withAttribute(
                        'session_id',
                        sprintf('user-session-%d', $authenticated_with->getId())
                    );
            } elseif ($authenticated_with instanceof ApiSubscription) {
                $request
                    ->withAttribute(
                        'session_id',
                        sprintf('api-token-%d', $authenticated_with->getId())
                    );
            }

            $logger->setAppRequest(new HttpRequest($request));
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }
}
