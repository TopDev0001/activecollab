<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\UrlMatcher;

use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedCollection;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedEntity;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRoute;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRouteInterface;
use ActiveCollab\Foundation\Urls\Router\RouteInterface;
use Angie\Inflector;
use Psr\Log\LoggerInterface;

class LiveUrlMatcher extends UrlMatcher
{
    private $mapper;

    public function __construct(
        RouteMapperInterface $mapper,
        RootUrlInterface $root_url,
        LoggerInterface $logger
    )
    {
        parent::__construct($root_url, $logger);

        $this->mapper = $mapper;
    }

    public function match(string $path_string, string $query_string): ?MatchedRouteInterface
    {
        $path_string = trim($path_string, '/');

        /** @var RouteInterface[] $routes */
        $routes = array_reverse($this->mapper->getRoutes());

        $matches = null;

        foreach ($routes as $route_name => $route) {
            if (preg_match($route->getRegularExpression(), $path_string, $matches)) {
                $url_params = $this->valuesFromMatchedPath(
                    $route->getNamedParameters(),
                    $route->getDefaults(),
                    $matches,
                    $query_string
                );

                if ($this->mapper->isMappedResource($route_name)) {
                    return new MatchedCollection(
                        $route_name,
                        $url_params,
                        Inflector::camelize($route_name)
                    );
                } elseif ($this->mapper->isMappedEntity($route_name)) {
                    return new MatchedEntity(
                        $route_name,
                        $url_params,
                        Inflector::camelize($route_name),
                        $url_params["{$route_name}_id"] ?? 0
                    );
                } else {
                    return new MatchedRoute($route_name, $url_params);
                }
            }
        }

        return null;
    }
}
