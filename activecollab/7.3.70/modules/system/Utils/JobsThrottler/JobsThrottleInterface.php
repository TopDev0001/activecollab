<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\JobsThrottler;

use ActiveCollab\JobsQueue\Queue\QueueInterface;

interface JobsThrottleInterface
{
    public function throttle(
        string $job_type,
        array $data = [],
        int $wait = 30,
        bool $leading = false,
        string $channel = QueueInterface::MAIN_CHANNEL
    ): void;
}
