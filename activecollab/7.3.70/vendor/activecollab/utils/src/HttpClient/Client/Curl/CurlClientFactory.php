<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\HttpClient\Client\Curl;

use ActiveCollab\HttpClient\Client\Factory\ClientFactory;
use ActiveCollab\HttpClient\Client\ClientInterface;

class CurlClientFactory extends ClientFactory
{
    protected function doCreateClient(): ClientInterface
    {
        return new CurlClient($this->getResponseFactory());
    }
}
