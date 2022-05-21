<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\UrlMatcher;

use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRouteInterface;
use Angie\Inflector;

abstract class CompiledUrlMatcher extends UrlMatcher
{
    public function match(string $path_string, string $query_string): ?MatchedRouteInterface
    {
        return $this->matchRouteFrom(
            trim($path_string, '/'),
            trim($query_string)
        );
    }

    protected function routeNameToCollectionName(string $route_name): string
    {
        return Inflector::camelize($route_name);
    }

    protected function routeNameToEntityName(string $route_name): string
    {
        return Inflector::camelize($route_name);
    }

    abstract protected function matchRouteFrom(string $path, string $query_string): ?MatchedRouteInterface;
}
