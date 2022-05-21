<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Subtask;

abstract class SubtaskLifeCycleEvent extends DataObjectLifeCycleEvent implements SubtaskLifeCycleEventInterface
{
    public function __construct(Subtask $object)
    {
        parent::__construct($object);
    }
}
