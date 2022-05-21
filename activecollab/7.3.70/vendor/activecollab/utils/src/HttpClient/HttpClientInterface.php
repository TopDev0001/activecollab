<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\HttpClient;

use ActiveCollab\HttpClient\Configure\ConfigureMiddlewareInterface;
use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    public function get(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface;
    public function post(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface;
    public function put(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface;
    public function patch(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface;
    public function delete(string $url, ConfigureMiddlewareInterface ...$configureMiddlewares): ResponseInterface;
}
