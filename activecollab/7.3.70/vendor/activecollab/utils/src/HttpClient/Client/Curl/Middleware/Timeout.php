<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\HttpClient\Client\Curl\Middleware;

use ActiveCollab\HttpClient\Client\ClientInterface;
use ActiveCollab\HttpClient\Configure\ClientMiddleware\ClientMiddlewareInterface;

class Timeout implements ClientMiddlewareInterface
{
    public function alter(ClientInterface $client): ClientInterface
    {
    }
}
