<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Sockets;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;

interface SocketInterface
{
    public function getRequests(
        string $event_type,
        DataObjectLifeCycleEventInterface $event,
        bool $requests_with_partial_data = false,
        int $delay = 1
    ): array;
}
