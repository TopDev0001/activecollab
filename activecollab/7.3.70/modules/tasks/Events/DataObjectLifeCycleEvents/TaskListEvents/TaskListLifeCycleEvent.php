<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use TaskList;

abstract class TaskListLifeCycleEvent extends DataObjectLifeCycleEvent implements TaskListLifeCycleEventInterface
{
    public function __construct(TaskList $object)
    {
        parent::__construct($object);
    }
}
