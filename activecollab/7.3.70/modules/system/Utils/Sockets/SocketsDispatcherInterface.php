<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Sockets;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use RealTimeIntegrationInterface;

interface SocketsDispatcherInterface
{
    public function dispatch(
        DataObjectLifeCycleEventInterface $event,
        string $event_type,
        bool $dispatch_partial_data = false,
        string $channel = RealTimeIntegrationInterface::JOBS_QUEUE_CHANNEL
    ): void;
}
