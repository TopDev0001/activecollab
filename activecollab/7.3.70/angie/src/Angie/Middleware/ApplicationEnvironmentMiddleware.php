<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use Angie\Middleware\Base\Middleware;
use AngieApplication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Angie\Middleware
 */
class ApplicationEnvironmentMiddleware extends Middleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $response = $response->withHeader('X-Angie-ApplicationVersion', AngieApplication::getVersion());

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }
}
