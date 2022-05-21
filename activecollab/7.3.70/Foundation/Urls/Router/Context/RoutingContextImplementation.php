<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Foundation\Urls\Router\Context;

use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use AngieApplication;
use Closure;

/**
 * Routing context implementation.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
trait RoutingContextImplementation
{
    public function getViewUrl(): string
    {
        return AngieApplication::getContainer()
            ->get(RouterInterface::class)
            ->assemble(
                $this->getRoutingContext(),
                $this->getRoutingContextParams()
            );
    }

    public function getUrlPath(): string
    {
        return url_to_path($this->getViewUrl());
    }

    /**
     * Return URL from cache, or assemble it, cache it and than return it.
     *
     * @param  string       $route_extension
     * @param  Closure|null $url_modifier
     * @param  bool         $url_modififer_condition
     * @return string
     */
    protected function getSubrouteUrlFromCache($route_extension, $url_modifier = null, $url_modififer_condition = true)
    {
        $url = AngieApplication::cache()->getByObject(
            $this,
            [
                'urls',
                $route_extension,
            ],
            function () use ($route_extension) {
                return AngieApplication::getContainer()
                    ->get(RouterInterface::class)
                    ->assemble(
                        $this->getRoutingContext() . '_' . $route_extension,
                        $this->getRoutingContextParams()
                    );
            }
        );

        if ($url_modififer_condition && $url_modifier instanceof Closure) {
            $modified_url = $url_modifier->__invoke($url);

            if ($modified_url) {
                return $modified_url;
            }
        }

        return $url;
    }

    abstract public function getRoutingContext(): string;
    abstract public function getRoutingContextParams(): array;
}
