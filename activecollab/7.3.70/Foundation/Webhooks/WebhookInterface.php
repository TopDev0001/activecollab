<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Webhooks;

use ActiveCollab\Foundation\Events\WebhookEvent\WebhookEventInterface;

interface WebhookInterface
{
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
    ];

    public function filterEvent(WebhookEventInterface $webhook_event): bool;
    public function getCustomQueryParams(WebhookEventInterface $webhook_event = null): string;
    public function getCustomHeaders(WebhookEventInterface $webhook_event = null): array;
}
