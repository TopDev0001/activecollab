<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Events\WebhookEvent;

use ActiveCollab\Foundation\Webhooks\WebhookInterface;

interface WebhookEventInterface
{
    public function getWebhookContext(): string;
    public function getWebhookEventType(): string;
    public function getWebhookPayload(WebhookInterface $webhook): ?array;
}
