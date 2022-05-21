<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReminderEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Reminder;

abstract class ReminderLifeCycleEvent extends DataObjectLifeCycleEvent implements ReminderLifeCycleEventInterface
{
    public function __construct(Reminder $object)
    {
        parent::__construct($object);
    }
}
