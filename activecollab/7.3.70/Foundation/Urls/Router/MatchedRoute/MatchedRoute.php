<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\MatchedRoute;

class MatchedRoute implements MatchedRouteInterface
{
    private $route_name;
    private $url_params;

    public function __construct(string $route_name, array $url_params)
    {
        $this->route_name = $route_name;
        $this->url_params = $url_params;
    }

    public function getRouteName(): string
    {
        return $this->route_name;
    }

    public function getUrlParams(): array
    {
        return $this->url_params;
    }

    public function getModule(): string
    {
        return $this->url_params['module'] ?? '';
    }

    public function getController(): string
    {
        return $this->url_params['controller'] ?? '';
    }

    public function getAction(): string
    {
        if (is_array($this->url_params['action'])) {
            return $this->url_params['action']['GET'] ?? '';
        } elseif (is_string($this->url_params['action'])) {
            return $this->url_params['action'];
        } else {
            return '';
        }
    }

    public function getArguments(): array
    {
        $result = [];

        foreach ($this->url_params as $url_param => $value) {
            if (!in_array($url_param, ['module', 'controller', 'action'])) {
                $result[$url_param] = $value;
            }
        }

        return $result;
    }
}
