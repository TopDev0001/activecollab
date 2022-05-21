<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TrashEvents;

use ActiveCollab\Foundation\Events\WebhookEvent\DataObjectLifeCycleWebhookEventTrait;
use User;

class RestoredFromTrashEvent extends TrashEvent implements RestoredFromTrashEventInterface
{
    use DataObjectLifeCycleWebhookEventTrait;

    public function getWebhookEventType(): string
    {
        if ($this->getObject() instanceof User) {
            return 'UserRestoredFromTrash';
        }

        return $this->prefixWebhookEventType('RestoredFromTrash');
    }
}
