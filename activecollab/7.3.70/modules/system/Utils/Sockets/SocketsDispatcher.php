<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Sockets;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use ActiveCollab\JobsQueue\Batches\BatchInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Logger\LoggerInterface;
use RealTimeIntegrationInterface;

class SocketsDispatcher implements SocketsDispatcherInterface
{
    private SocketInterface $socket;
    private JobsDispatcherInterface $jobs;
    private LoggerInterface $logger;

    public function __construct(
        SocketInterface $socket,
        JobsDispatcherInterface $jobs,
        LoggerInterface $logger
    ) {
        $this->socket = $socket;
        $this->jobs = $jobs;
        $this->logger = $logger;
    }

    public function dispatch(
        DataObjectLifeCycleEventInterface $event,
        string $event_type,
        bool $dispatch_partial_data = false,
        string $channel = RealTimeIntegrationInterface::JOBS_QUEUE_CHANNEL
    ): void
    {
        $requests = $this->socket->getRequests($event_type, $event, $dispatch_partial_data);

        if (!empty($requests)) {
            if (count($requests) === 1) {
                $this->jobs->dispatch($requests[0], $channel);
            } else {
                $this->jobs->batch(
                    $event_type,
                    function (BatchInterface $batch) use ($channel, $requests) {
                        foreach ($requests as $request) {
                            $batch->dispatch($request, $channel);
                        }
                    }
                );
            }
        } else {
            $this->logger->debug(
                'Skipping event type {event_type}, no requests defined.',
                ['event_type' => $event_type]
            );
        }
    }
}
