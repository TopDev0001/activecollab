<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents;

class ProjectReopenedEvent extends ProjectUpdatedEvent implements ProjectReopenedEventInterface
{
    public function getWebhookEventType(): string
    {
        return 'ProjectReopened';
    }
}
