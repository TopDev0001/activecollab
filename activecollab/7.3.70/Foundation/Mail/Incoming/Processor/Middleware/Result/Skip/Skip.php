<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\Skip;

use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\MiddlewareResult;

class Skip extends MiddlewareResult implements SkipInterface
{
    private ?string $skip_reason;

    public function __construct(string $skip_reason = null)
    {
        $this->skip_reason = $skip_reason;
    }

    public function getReason(): ?string
    {
        return $this->skip_reason;
    }
}
