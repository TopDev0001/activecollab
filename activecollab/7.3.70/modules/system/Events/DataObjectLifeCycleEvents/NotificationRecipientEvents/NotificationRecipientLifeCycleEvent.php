<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\NotificationRecipientEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use NotificationRecipient;

abstract class NotificationRecipientLifeCycleEvent extends DataObjectLifeCycleEvent implements NotificationRecipientLifeCycleEventInterface
{
    public function __construct(NotificationRecipient $object)
    {
        parent::__construct($object);
    }
}
