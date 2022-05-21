<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor;

use ActiveCollab\Foundation\Mail\Incoming\Message\MessageInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Middleware\Result\MiddlewareResultInterface;
use EmailMessageInterface;

interface ProcessorInterface
{
    public function processEmailMessage(
        EmailMessageInterface $message,
        string $source
    ): ?MiddlewareResultInterface;
    public function process(MessageInterface $message, string $source): ?MiddlewareResultInterface;
}
