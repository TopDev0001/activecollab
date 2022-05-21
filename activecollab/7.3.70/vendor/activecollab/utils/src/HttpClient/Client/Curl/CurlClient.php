<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\HttpClient\Client\Curl;

use ActiveCollab\HttpClient\Client\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class CurlClient extends Client implements CurlClientInterface
{
    private ResponseFactoryInterface $responseFactory;

    /**
     * @var resource|null
     */
    private $handle;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
    }

    public function setOption(int $option, $value): CurlClientInterface
    {
        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse();
    }
}
