<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Angie\Middleware
 */
interface MiddlewareInterface
{
    const ACTION_RESULT_ATTRIBUTE = 'controller_action_result';

    /**
     * Make sure that middlewares can run as PSR-7 middlewares.
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null);
}
