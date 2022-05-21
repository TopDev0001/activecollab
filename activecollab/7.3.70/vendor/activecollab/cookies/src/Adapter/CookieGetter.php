<?php

/*
 * This file is part of the Active Collab Cookies project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Cookies\Adapter;

use Dflydev\FigCookies\Cookies;
use Psr\Http\Message\ServerRequestInterface;

class CookieGetter implements CookieGetterInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function exists(ServerRequestInterface $request): bool
    {
        return Cookies::fromRequest($request)->has($this->name);
    }

    public function get(
        ServerRequestInterface $request,
        $default = null
    )
    {
        $cookies = Cookies::fromRequest($request);

        if ($cookies->has($this->name)) {
            return $cookies->get($this->name)->getValue();
        }

        return $default;
    }
}
