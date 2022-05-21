<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookLogger\Reference;

interface WebhookLogReferenceInterface
{
    public function getReference(): int;
    public function getTimeReference(): float;
    public function getExecutionTime(float $current_time = null): int;
}
