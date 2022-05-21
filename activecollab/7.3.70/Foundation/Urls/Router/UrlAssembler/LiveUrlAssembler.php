<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\UrlAssembler;

use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;

class LiveUrlAssembler extends UrlAssembler
{
    private $mapper;

    public function __construct(RootUrlInterface $root_url, RouteMapperInterface $mapper)
    {
        parent::__construct($root_url);

        $this->mapper = $mapper;
    }

    protected function getRouteAssemblyParts(string $route_name): array
    {
        $route = $this->mapper->getRoute($route_name);

        if ($route) {
            return [
                $route->getRouteString(),
                $route->getDefaults(),
            ];
        } else {
            return [
                null,
                null,
            ];
        }
    }
}
