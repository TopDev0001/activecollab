<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\UserInternalRateEvents;

class UserInternalRateCreatedEvent extends UserInternalRateLifeCycleEvent implements UserInternalRateCreatedEventInterface
{
    public function getWebhookEventType(): string
    {
        return self::NAME;
    }
}
