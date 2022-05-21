<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use Angie\Middleware\Base\Middleware;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * User agent extractor middleware.
 *
 * @package Angie\Middleware
 */
class UserAgentMiddleware extends Middleware
{
    /**
     * @var string
     */
    private $user_agent_attribute_name;

    /**
     * @var callable|null
     */
    private $on_user_agent_resolved;

    /**
     * UserAgentMiddleware constructor.
     *
     * @param string $user_agent_attribute_name
     */
    public function __construct($user_agent_attribute_name, callable $on_user_agent_resolved = null, LoggerInterface $logger = null)
    {
        parent::__construct($logger);

        if (!is_string($user_agent_attribute_name) || empty($user_agent_attribute_name)) {
            throw new InvalidArgumentException('User agent attribute names are required.');
        }

        $this->user_agent_attribute_name = $user_agent_attribute_name;
        $this->on_user_agent_resolved = $on_user_agent_resolved;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $user_agent = $this->getUserAgentFrom($request);

        $request = $request->withAttribute($this->user_agent_attribute_name, $user_agent);

        if ($this->on_user_agent_resolved) {
            call_user_func($this->on_user_agent_resolved, $user_agent);
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * Detect and return user agent string.
     *
     * @return string
     */
    private function getUserAgentFrom(ServerRequestInterface $request)
    {
        $server_params = $request->getServerParams();

        return empty($server_params['HTTP_USER_AGENT']) ? '' : (string) $server_params['HTTP_USER_AGENT'];
    }
}
