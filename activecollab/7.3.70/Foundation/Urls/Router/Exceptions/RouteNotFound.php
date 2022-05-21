<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Exceptions;

use InvalidArgumentException;
use Throwable;

class RouteNotFound extends InvalidArgumentException
{
    public function __construct(
        string $route_name,
        $code = 0,
        Throwable $previous = null
    )
    {
        parent::__construct(
            sprintf('Route "%s" not found.', $route_name),
            $code,
            $previous
        );
    }
}
