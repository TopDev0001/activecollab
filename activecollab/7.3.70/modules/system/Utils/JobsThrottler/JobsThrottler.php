<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\JobsThrottler;

use ActiveCollab\JobsQueue\Jobs\JobInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\JobsQueue\Queue\QueueInterface;

class JobsThrottler implements JobsThrottleInterface
{
    private JobsDispatcherInterface $dispatcher;

    public function __construct(
        JobsDispatcherInterface $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function throttle(
        string $job_type,
        array $data = [],
        int $wait = 30,
        bool $leading = false,
        string $channel = QueueInterface::MAIN_CHANNEL
    ): void
    {
        if (!$this->dispatcher->exists($job_type, $data)) {
            if ($leading) {
                 $this->dispatchJob(
                    $job_type,
                    $data,
                    0,
                    $channel
                );
            }

            $this->dispatchJob(
                $job_type,
                $data,
                $wait,
                $channel
            );
        }
    }

    private function prepareJob(
        string $job_type,
        array $data,
        int $time
    ): JobInterface
    {
        return new $job_type(
            array_merge(
                $data,
                $time ? ['delay' => $time] : [],
            )
        );
    }

    private function dispatchJob(
        string $job_type,
        array $data,
        int $time,
        string $channel
    ) {
        $this->dispatcher->dispatch(
            $this->prepareJob(
                $job_type,
                $data,
                $time
            ),
            $channel
        );
    }
}
