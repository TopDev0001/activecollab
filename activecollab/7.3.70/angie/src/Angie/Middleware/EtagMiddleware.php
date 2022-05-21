<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use AccessLogs;
use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use Angie\Middleware\Base\Middleware;
use Angie\Utils\CurrentTimestamp;
use DataManager;
use DataObjectCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use User;

/**
 * @package Angie\Middleware
 */
class EtagMiddleware extends Middleware
{
    /**
     * @var CurrentTimestampInterface|null
     */
    private $current_timestamp;

    /**
     * EtagMiddleware constructor.
     */
    public function __construct(
        CurrentTimestampInterface $current_timestamp = null,
        LoggerInterface $logger = null
    )
    {
        parent::__construct($logger);

        $this->current_timestamp = $current_timestamp ? $current_timestamp : new CurrentTimestamp();
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $etag = $this->checkEtag(
            $request->getAttribute('authenticated_user'),
            $this->getCleanEtagFromRequest($request)
        );

        if ($etag) {
            return $response
                ->withStatus(304)
                ->withHeader('Cache-Control', 'public, max-age=0')
                ->withHeader(
                    'Expires',
                    gmdate(
                        'D, d M Y H:i:s',
                        ($this->current_timestamp->getCurrentTimestamp() + 315360000)) . ' GMT'
                )
                ->withHeader('Etag', $etag);
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * Intercept HTTP bootstraping and handle 304 if we have a properly cached resource.
     *
     * @param  string      $etag
     * @return string|null
     */
    private function checkEtag(?AuthenticatedUserInterface $user, $etag)
    {
        if ($etag && substr_count($etag, ',') == 5 && $user instanceof User) {
            [
                $application_version,
                $type,
                $model,
                $id,
                $email,
                $hash
            ] = explode(',', trim($etag, '"'));

            if ($application_version == APPLICATION_VERSION && $user->getEmail() == $email) {
                $etag_ok = false;

                if (str_contains($type, 'collection')) {
                    if (class_exists($model)) {
                        $model_class = new ReflectionClass($model);

                        if ($model_class->isSubclassOf(DataManager::class)) {
                            $resource = $type === 'cursor_collection'
                                ? call_user_func("$model::prepareCursorCollection", $id, $user)
                                : call_user_func("$model::prepareCollection", $id, $user);

                            if ($resource instanceof DataObjectCollection) {
                                $clean_resource_etag = $this->getCleanEtag($resource->getTag($user));

                                if ($this->getLogger()) {
                                    $this->getLogger()->debug(
                                        'Comparing etag from request "{etag_from_request}" with {model} collection "{collection}" which returned "{resource_etag}".',
                                        [
                                            'etag_from_request' => $etag,
                                            'model' => $model,
                                            'collection' => $id,
                                            'resource_etag' => $clean_resource_etag,
                                        ]
                                    );
                                }

                                $etag_ok = $clean_resource_etag == $etag;
                            }
                        }
                    }
                } elseif ($type === 'object') {
                    $etag_ok = call_user_func("$model::checkObjectEtag", $id, $hash);

                    if ($etag_ok) {
                        AccessLogs::logAccessOnObjectEtagMatch($model, $id, $email);
                    }
                }

                if ($etag_ok) {
                    return $etag;
                }
            }
        }

        return null;
    }

    public function getCleanEtagFromRequest(ServerRequestInterface $request)
    {
        $etag = trim((string) $request->getHeaderLine('If-None-Match'));

        if (!empty($etag)) {
            $clean_etag = $this->getCleanEtag($etag);

            if ($this->getLogger()) {
                $this->getLogger()->debug(
                    'Etag "{raw_etag}" extracted from request, and cleaned to "{clean_etag}" value.',
                    [
                        'raw_etag' => $etag,
                        'clean_etag' => $clean_etag,
                    ]
                );
            }

            return $clean_etag;
        }

        return $etag;
    }

    private function getCleanEtag($etag)
    {
        if (is_string($etag)) {
            $result = $etag;

            // Remove prefix that nginx adds for "weak" etag when compressing responses.
            if (str_starts_with($result, 'W/')) {
                $result = mb_substr($result, 2);
            }

            // Remote suffix that Apache adds when compressing responses.
            if (str_ends_with($result, '-gzip')) {
                $result = mb_substr($result, 0, mb_strlen($result) - 5);
            }

            $result = trim($result, '"');

            return $result;
        } else {
            return '';
        }
    }
}
