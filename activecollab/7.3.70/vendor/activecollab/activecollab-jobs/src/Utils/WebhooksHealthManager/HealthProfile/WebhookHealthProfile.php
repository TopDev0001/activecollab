<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\HealthProfile;

class WebhookHealthProfile implements WebhookHealthProfileInterface
{
    private int $successes;
    private int $failures;
    private int $exceptions;

    public function __construct(
        int $successes,
        int $failures,
        int $exceptions
    )
    {
        $this->successes = $successes;
        $this->failures = $failures;
        $this->exceptions = $exceptions;
    }

    public function countAll(): int
    {
        return $this->successes + $this->failures + $this->exceptions;
    }

    public function countAllFailures(): int
    {
        return $this->failures + $this->exceptions;
    }

    public function getFailureRate(): int
    {
        if ($this->countAll() === 0) {
            return 0;
        }

        return (int) ceil(100 * $this->countAllFailures() / $this->countAll());
    }

    public function countSuccesses(): int
    {
        return $this->successes;
    }

    public function countFailures(): int
    {
        return $this->failures;
    }

    public function countExceptions(): int
    {
        return $this->exceptions;
    }
}
