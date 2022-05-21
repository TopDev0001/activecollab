<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\HttpClient;

use ActiveCollab\HttpClient\Client\ClientInterface;
use ActiveCollab\HttpClient\Client\Factory\ClientFactoryInterface;
use ActiveCollab\HttpClient\Configure\ClientMiddleware\ClientMiddlewareInterface;
use ActiveCollab\HttpClient\Configure\ConfigureMiddlewareInterface;
use ActiveCollab\HttpClient\Configure\RequestMiddleware\RequestMiddlewareInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClient implements HttpClientInterface
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    private ClientFactoryInterface $clientFactory;
    private RequestFactoryInterface $requestFactory;

    public function __construct(ClientFactoryInterface $clientFactory, RequestFactoryInterface $requestFactory)
    {
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
    }

    public function get(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface
    {
        return $this->prepareClient(...$configureMiddlewares)->sendRequest(
            $this->prepareRequest(self::METHOD_GET, $url, ...$configureMiddlewares)
        );
    }

    public function post(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface
    {
        return $this->prepareClient(...$configureMiddlewares)->sendRequest(
            $this->prepareRequest(self::METHOD_POST, $url, ...$configureMiddlewares)
        );
    }

    public function put(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface
    {
        return $this->prepareClient(...$configureMiddlewares)->sendRequest(
            $this->prepareRequest(self::METHOD_PUT, $url, ...$configureMiddlewares)
        );
    }

    public function patch(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface
    {
        return $this->prepareClient(...$configureMiddlewares)->sendRequest(
            $this->prepareRequest(self::METHOD_PATCH, $url, ...$configureMiddlewares)
        );
    }

    public function delete(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface
    {
        return $this->prepareClient(...$configureMiddlewares)->sendRequest(
            $this->prepareRequest(self::METHOD_DELETE, $url, ...$configureMiddlewares)
        );
    }

    private function prepareClient(ConfigureMiddlewareInterface ...$configureMiddlewares): ClientInterface
    {
        $client = $this->clientFactory->createClient();

        foreach ($configureMiddlewares as $configureMiddleware) {
            if ($configureMiddleware instanceof ClientMiddlewareInterface) {
                $client = $configureMiddleware->alter($client);
            }
        }

        return $client;
    }

    private function prepareRequest(
        string $httpMethod,
        string $url,
        ConfigureMiddlewareInterface ...$configureMiddlewares
    ): RequestInterface
    {
        $request = $this->requestFactory->createRequest($httpMethod, $url);

        foreach ($configureMiddlewares as $middleware) {
            if ($middleware instanceof RequestMiddlewareInterface) {
                $request = $middleware->alter($request);
            }
        }

        return $request;
    }
}
