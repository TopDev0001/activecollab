<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents;

use ActiveCollab\Foundation\Events\WebhookEvent\DataObjectLifeCycleWebhookEventTrait;

class ConversationUserDeletedEvent extends ConversationUserLifeCycleEvent implements ConversationUserDeletedEventInterface
{
    use DataObjectLifeCycleWebhookEventTrait;

    public function getWebhookEventType(): string
    {
        return 'ConversationUserDeleted';
    }

    public function whoShouldBeNotified(): array
    {
        return $this->getObject()->getConversation()->getMemberIds();
    }
}
