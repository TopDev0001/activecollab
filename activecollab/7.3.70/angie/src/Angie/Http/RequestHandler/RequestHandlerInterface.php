<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Angie\Http
 */
interface RequestHandlerInterface
{
    /**
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface &$request, ResponseInterface $response);
}
