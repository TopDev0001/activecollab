<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\HttpClient\Configure\RequestMiddleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class JsonPayload implements RequestMiddlewareInterface
{
    private StreamFactoryInterface $streamFactory;
    private $payload;

    public function __construct(StreamFactoryInterface $streamFactory, $payload)
    {
        $this->streamFactory = $streamFactory;
        $this->payload = $payload;
    }

    public function alter(RequestInterface $request): RequestInterface
    {
        return $request
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(
                $this->streamFactory->createStream(
                    json_encode(
                        $this->payload,
                        JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    )
                )
            );
    }
}
