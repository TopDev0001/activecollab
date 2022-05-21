<?php

/*
 * This file is part of the Active Collab Cookies project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Cookies\Adapter;

use Psr\Http\Message\ServerRequestInterface;

interface CookieGetterInterface
{
    public function exists(ServerRequestInterface $request): bool;
    public function get(ServerRequestInterface $request, $default = null);
}
