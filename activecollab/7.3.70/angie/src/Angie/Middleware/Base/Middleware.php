<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Middleware\Base;

use Angie\Middleware\MiddlewareInterface;
use Psr\Log\LoggerInterface;

abstract class Middleware implements MiddlewareInterface
{
    private ?LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    protected function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }
}
