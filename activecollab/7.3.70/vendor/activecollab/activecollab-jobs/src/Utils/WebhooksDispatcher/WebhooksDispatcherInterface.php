<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher;

use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\WebhookDispatchResultInterface;

interface WebhooksDispatcherInterface
{
    public function dispatch(
        int $instance_id,
        int $webhook_id,
        string $url,
        array $headers,
        string $json_payload,
        int $timeout = 600,
        bool $verify_peer = false
    ): WebhookDispatchResultInterface;
}
