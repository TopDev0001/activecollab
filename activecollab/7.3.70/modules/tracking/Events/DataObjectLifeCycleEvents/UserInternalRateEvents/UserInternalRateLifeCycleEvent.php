<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare (strict_types=1);

namespace ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\UserInternalRateEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use ActiveCollab\Foundation\Events\WebhookEvent\DataObjectLifeCycleWebhookEventTrait;
use UserInternalRate;

abstract class UserInternalRateLifeCycleEvent extends DataObjectLifeCycleEvent implements UserInternalRateLifeCycleEventInterface
{
    use DataObjectLifeCycleWebhookEventTrait;

    public function __construct(UserInternalRate $userInternalRate)
    {
        parent::__construct($userInternalRate);
    }
}
