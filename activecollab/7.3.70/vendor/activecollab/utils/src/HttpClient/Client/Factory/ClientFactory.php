<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\HttpClient\Client\Factory;

use ActiveCollab\HttpClient\Client\ClientInterface;
use ActiveCollab\HttpClient\Configure\ClientMiddleware\ClientMiddlewareInterface;
use Psr\Http\Message\ResponseFactoryInterface;

abstract class ClientFactory implements ClientFactoryInterface
{
    private ResponseFactoryInterface $responseFactory;
    private array $defaultMiddlewares;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ClientMiddlewareInterface ...$defaultMiddlewares
    )
    {
        $this->responseFactory = $responseFactory;
        $this->defaultMiddlewares = $defaultMiddlewares;
    }

    public function createClient(): ClientInterface
    {
        $client = $this->doCreateClient();

        foreach ($this->getDefaultMiddlewares() as $defaultMiddleware) {
            $client = $defaultMiddleware->alter($client);
        }

        return $client;
    }

    abstract protected function doCreateClient(): ClientInterface;

    protected function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @return ClientMiddlewareInterface[]
     */
    protected function getDefaultMiddlewares(): array
    {
        return $this->defaultMiddlewares;
    }
}
