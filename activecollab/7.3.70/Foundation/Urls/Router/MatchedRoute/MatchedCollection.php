<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\MatchedRoute;

class MatchedCollection extends MatchedRoute implements MatchedCollectionInterface
{
    private $resource_name;

    public function __construct(string $route_name, array $url_params, string $resource_name)
    {
        parent::__construct($route_name, $url_params);

        $this->resource_name = $resource_name;
    }

    public function getResourceName(): string
    {
        return $this->resource_name;
    }
}
