<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Middleware;

use Angie\Error;
use Angie\Http\Encoder\EncoderInterface;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandlerMiddleware
{
    private EncoderInterface $encoder;
    private ?LoggerInterface $logger;

    public function __construct(
        EncoderInterface $encoder,
        LoggerInterface $logger = null
    )
    {
        $this->encoder = $encoder;
        $this->logger = $logger;
    }

    protected function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Callable implementation.
     *
     * Note: Method signature is different from other middlewares because nature of this middleware is different in Zend
     * Stratigility implementation. Details:
     *
     * https://zendframework.github.io/zend-stratigility/error-handlers/
     *
     * @param Exception|Throwable $error
     */
    public function __invoke($error, ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$error instanceof Throwable) {
            return $response->withStatus(500);
        }

        if ($this->getLogger()) {
            $error_log_attributes = [
                'message' => $error->getMessage(),
                'exception' => $error,
            ];

            if ($error instanceof Error) {
                $error_log_attributes = array_merge($error_log_attributes, $error->getParams());
            }

            $this->getLogger()->error('Client facing exception: {message}.', $error_log_attributes);
        }

        return $this->encoder->encode($error, $request, $response)[1];
    }
}
