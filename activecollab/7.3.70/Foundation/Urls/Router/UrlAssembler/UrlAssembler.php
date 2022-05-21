<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\UrlAssembler;

use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Urls\Router\Exceptions\MissingUrlArgument;
use ActiveCollab\Foundation\Urls\Router\Exceptions\RouteNotFound;

abstract class UrlAssembler implements UrlAssemblerInterface
{
    private $root_url;

    public function __construct(RootUrlInterface $root_url)
    {
        $this->root_url = $root_url;
    }

    public function assemble(string $name, array $data = []): string
    {
        [
            $route,
            $defaults,
        ] = $this->getRouteAssemblyParts($name);

        if (empty($route) || !is_array($defaults)) {
            throw new RouteNotFound($name);
        }

        return $this->buildUrl($route, $data, $defaults);
    }

    protected function buildUrl(string $route, array $data, array $defaults)
    {
        $path_parts = [];
        $query_parts = [];
        $part_names = [];

        // Prepare path param
        foreach (explode('/', $route) as $key => $part) {
            if (substr($part, 0, 1) == ':') {
                $part_name = substr($part, 1);
                $part_names[] = $part_name;

                if (isset($data[$part_name])) {
                    $path_parts[$key] = $this->encodeForUrl($data[$part_name]);
                } elseif (isset($defaults[$part_name])) {
                    $path_parts[$key] = $this->encodeForUrl($defaults[$part_name]);
                } else {
                    throw new MissingUrlArgument($route, $part_name);
                }
            } else {
                $path_parts[$key] = $part;
            }
        }

        foreach ($data as $k => $v) {
            if (!in_array($k, $part_names)) {
                $query_parts[$k] = $this->encodeForUrl($v);
            }
        }

        $url = with_slash($this->getBaseUrl()) . trim(implode('/', $path_parts), '/');

        if (!empty($query_parts)) {
            $url .= '?' . http_build_query($query_parts, '', '&');
        }

        return $url;
    }

    private function encodeForUrl($value): string
    {
        if ($value === false) {
            return '0';
        }

        return (string) $value;
    }

    private function getBaseUrl(): string
    {
        return $this->root_url->getUrl();
    }

    abstract protected function getRouteAssemblyParts(string $route_name): array;
}
