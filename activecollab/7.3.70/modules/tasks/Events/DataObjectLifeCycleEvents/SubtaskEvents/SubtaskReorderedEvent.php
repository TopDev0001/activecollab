<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents;

class SubtaskReorderedEvent extends SubtaskUpdatedEvent implements SubtaskReorderedEventInterface
{
    public function getWebhookEventType(): string
    {
        return 'SubtaskReordered';
    }
}
