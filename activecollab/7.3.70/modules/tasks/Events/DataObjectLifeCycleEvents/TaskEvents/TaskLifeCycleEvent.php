<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Task;

abstract class TaskLifeCycleEvent extends DataObjectLifeCycleEvent implements TaskLifeCycleEventInterface
{
    public function __construct(Task $object)
    {
        parent::__construct($object);
    }
}
