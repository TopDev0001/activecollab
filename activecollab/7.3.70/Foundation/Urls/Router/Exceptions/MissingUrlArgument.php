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

class MissingUrlArgument extends InvalidArgumentException
{
    public function __construct(
        string $route_string,
        string $part_name,
        int $code = 0,
        Throwable $previous = null
    )
    {
        if (empty($message)) {
            $message = "Failed to assemble '$route_string' based on provided data";
        }

        parent::__construct(
            sprintf('Failed to assemble URL for "%s" route. Part "%s" missing.', $route_string, $part_name),
            $code,
            $previous
        );
    }
}
