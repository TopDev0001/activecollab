<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Middleware;

use ActiveCollab\Foundation\Urls\Router\Exceptions\RouteNotMatched;
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use Angie\Controller\Error\ActionForMethodNotFound;
use Angie\Http\Encoder\EncoderInterface;
use Angie\Http\Response\StatusResponse\NotFoundStatusResponse;
use Angie\Http\Response\StatusResponse\StatusResponse;
use Angie\Inflector;
use Angie\Middleware\Base\EncoderMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class RouterMiddleware extends EncoderMiddleware
{
    private $url_matcher;

    public function __construct(
        EncoderInterface $encoder,
        UrlMatcherInterface $url_matcher,
        LoggerInterface $logger = null
    )
    {
        parent::__construct($encoder, $logger);

        $this->url_matcher = $url_matcher;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    )
    {
        if ($request->getMethod() == 'OPTIONS') {
            return $response->withStatus(200)->withHeader('Allow', 'GET,PUT,DELETE,OPTIONS');
        }

        $query_string_params = $request->getQueryParams();

        $path_info = isset($query_string_params['path_info']) && $query_string_params['path_info']
            ? $query_string_params['path_info']
            : '/';

        try {
            $matched_route = $this->url_matcher->mustMatch($path_info, $request->getUri()->getQuery());

            $url_params = $matched_route->getUrlParams();

            if (empty($url_params['action'])) {
                $url_params['action'] = 'index';
            }

            if (is_array($url_params) && !empty($url_params['module']) && !empty($url_params['controller'])) {
                $request = $this->overrideQueryParams($request, $url_params);
            } else {
                return $this->getEncoder()->encode(
                    new StatusResponse(500, 'Runtime error when routing user request.'),
                    $request,
                    $response
                )[1];
            }
        } catch (RouteNotMatched $e) {
            if ($this->getLogger()) {
                $this->getLogger()->error('Path {path_info} does not match any of the routes', [
                    'path_info' => $path_info,
                    'exception' => $e,
                ]);
            }

            return $this->getEncoder()->encode(new NotFoundStatusResponse(), $request, $response)[1];
        } catch (ActionForMethodNotFound $e) {
            if ($this->getLogger()) {
                $this->getLogger()->error(
                    'Action not found for the method',
                    array_merge(
                        $e->getParams(),
                        [
                            'exception' => $e,
                        ]
                    )
                );
            }

            return $this->getEncoder()->encode(
                new StatusResponse(405, 'Method Not Allowed.'),
                $request,
                $response
            )[1];
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    private function overrideQueryParams(ServerRequestInterface $request, array $url_params): RequestInterface
    {
        $initial_query_params = $request->getQueryParams();
        $url_params = is_array($url_params) ? $url_params : [];

        $module = $url_params['module'] ?? DEFAULT_MODULE;
        $controller = $url_params['controller'] ?? DEFAULT_CONTROLLER;

        if (!empty($url_params['action'])) {
            $action = [];

            if (is_string($url_params['action'])) {
                $underscored_action = Inflector::underscore(trim($url_params['action']));

                // @TODO Should be reduced to GET only!
                $action['GET'] = $underscored_action;
                $action['POST'] = $underscored_action;
                $action['PUT'] = $underscored_action;
                $action['DELETE'] = $underscored_action;
            } elseif (is_array($url_params['action'])) {
                foreach ($url_params['action'] as $method => $controller_action) {
                    $action[strtoupper($method)] = Inflector::underscore($controller_action);
                }
            }
        } else {
            $action = [
                'GET' => 'index',
                'POST' => 'index',
            ];
        }

        if (array_key_exists($request->getMethod(), $action)) {
            $action_for_method = $action[$request->getMethod()];
        } else {
            throw new ActionForMethodNotFound($controller, first($action), $request->getMethod());
        }

        $request = $request
            ->withAttribute('module', $module)
            ->withAttribute('controller', $controller)
            ->withAttribute('action', $action_for_method)
            ->withQueryParams(array_merge($initial_query_params, $url_params));

        return $request;
    }
}
