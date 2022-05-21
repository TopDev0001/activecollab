<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result;

use Throwable;

class ExceptionWebhookDispatchResult implements WebhookDispatchResultInterface
{
    private Throwable $exception;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function isSuccess(): bool
    {
        return false;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
