<?php

/*
 * This file is part of the Active Collab Cookies project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Cookies\Adapter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface CookieManipulatorInterface
{
    public function applyToRequest(RequestInterface $request): RequestInterface;
    public function applyToResponse(ResponseInterface $response): ResponseInterface;
}
