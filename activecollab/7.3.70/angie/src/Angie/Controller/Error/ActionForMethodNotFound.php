<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Controller\Error;

use Angie\Error;

class ActionForMethodNotFound extends Error
{
    public function __construct(
        string $controller,
        string $action,
        string $method,
        string $message = null
    )
    {
        if (empty($message)) {
            $message = "Controller action $controller::$action() is not available for $method method";
        }

        parent::__construct(
            $message,
            [
                'controller' => $controller,
                'action' => $action,
                'method' => $method,
            ]
        );
    }
}
