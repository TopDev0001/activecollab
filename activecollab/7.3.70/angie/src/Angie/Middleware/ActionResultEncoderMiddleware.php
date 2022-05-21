<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use Angie\Http\Encoder\EncoderInterface;
use Angie\Middleware\Base\EncoderMiddleware;
use Angie\Utils\CurrentTimestamp;
use Exception;
use IEtag;
use ImpossibleCollectionError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use User;

class ActionResultEncoderMiddleware extends EncoderMiddleware
{
    private $current_timestamp;

    public function __construct(
        EncoderInterface $encoder,
        CurrentTimestampInterface $current_timestamp = null,
        LoggerInterface $logger = null
    )
    {
        parent::__construct($encoder, $logger);

        $this->current_timestamp = $current_timestamp ? $current_timestamp : new CurrentTimestamp();
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $action_result = $request->getAttribute(self::ACTION_RESULT_ATTRIBUTE);

        if ($action_result !== null) {
            /** @var ResponseInterface $response */
            $response = $this->getEncoder()
                ->encode(
                    $request->getAttribute(self::ACTION_RESULT_ATTRIBUTE),
                    $request,
                    $response
                )[1];

            if ($this->canBeEtagged($action_result, $request)) {
                try {
                    $etag = (string) $action_result->getTag($request->getAttribute('authenticated_user'));

                    if ($etag) {
                        $response = $response
                            ->withHeader('Cache-Control', 'public, max-age=0')
                            ->withHeader(
                                'Expires',
                                sprintf(
                                    '%s GMT',
                                    gmdate(
                                        'D, d M Y H:i:s',
                                        ($this->current_timestamp->getCurrentTimestamp() + 315360000)
                                    )
                                )
                            )
                            ->withHeader('Etag', $etag);
                    }
                } catch (Exception $e) {
                    if ($this->getLogger()) {
                        $this->getLogger()->log(
                            $this->getLogLevelByException($e),
                            'Failed to etag an action result due to an exception: {reason}',
                            [
                                'action_result_type' => gettype($action_result),
                                'reason' => $e->getMessage(),
                                'exception' => $e,
                            ]
                        );
                    }
                }
            }
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    private function getLogLevelByException(Exception $e): string
    {
        return $e instanceof ImpossibleCollectionError
            ? LogLevel::NOTICE
            : LogLevel::ERROR;
    }

    private function canBeEtagged($action_result, ServerRequestInterface $request): bool
    {
        return $action_result instanceof IEtag && $request->getAttribute('authenticated_user') instanceof User;
    }
}
