<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Events\WebhookEvent;

use ActiveCollab\Foundation\Webhooks\WebhookInterface;
use DataObject;
use Webhook;

trait DataObjectLifeCycleWebhookEventTrait
{
    public function getWebhookContext(): string
    {
        return get_class($this->getObject()) . ' #' . $this->getObject()->getId();
    }

    /**
     * @param Webhook|WebhookInterface $webhook
     */
    public function getWebhookPayload(WebhookInterface $webhook): ?array
    {
        return $webhook->getPayload($this->getWebhookEventType(), $this->getObject());
    }

    abstract public function getWebhookEventType(): string;
    abstract public function getObject(): DataObject;
}
