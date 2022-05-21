<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Router\Exceptions;

use RuntimeException;
use Throwable;

class RouteNotMatched extends RuntimeException
{
    public function __construct(
        string $route_name,
        $code = 0,
        Throwable $previous = null
    )
    {
        parent::__construct(
            sprintf('String "%s" does not match any of mapped routes', $route_name),
            $code,
            $previous
        );
    }
}
