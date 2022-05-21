<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger\Reference;

class WebhookLogReference implements WebhookLogReferenceInterface
{
    private int $reference;
    private float $time_reference;

    public function __construct(int $reference, float $time_reference)
    {
        $this->reference = $reference;
        $this->time_reference = $time_reference;
    }

    public function getReference(): int
    {
        return $this->reference;
    }

    public function getTimeReference(): float
    {
        return $this->time_reference;
    }

    public function getExecutionTime(float $current_time = null): int
    {
        if ($current_time === null) {
            $current_time = microtime(true);
        }

        return (int) floor(($current_time - $this->time_reference) * 1000);
    }
}
