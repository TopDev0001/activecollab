<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ActivityLogEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use ActivityLog;

abstract class ActivityLogLifeCycleEvent extends DataObjectLifeCycleEvent implements ActivityLogLifeCycleEventInterface
{
    public function __construct(ActivityLog $object)
    {
        parent::__construct($object);
    }
}
