<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents;

use ActiveCollab\Foundation\Events\WebhookEvent\DataObjectLifeCycleWebhookEventTrait;
use ActiveCollab\Module\System\EventListeners\BadgeCountEvents\BadgeCountChangedEventInterface;

class MessageDeletedEvent extends MessageLifeCycleEvent implements MessageDeletedEventInterface, BadgeCountChangedEventInterface
{
    use DataObjectLifeCycleWebhookEventTrait;

    public function getWebhookEventType(): string
    {
        return 'MessageDeleted';
    }
}
