<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents;

class SubtaskMoveToTrashEvent extends SubtaskUpdatedEvent implements SubtaskMoveToTrashEventInterface
{
    public function getWebhookEventType(): string
    {
        return 'SubtaskMoveToTrash';
    }
}
