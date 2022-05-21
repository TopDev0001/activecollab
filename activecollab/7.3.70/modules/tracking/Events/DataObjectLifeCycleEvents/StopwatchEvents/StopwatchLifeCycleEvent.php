<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\StopwatchEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Stopwatch;

abstract class StopwatchLifeCycleEvent extends DataObjectLifeCycleEvent implements StopwatchLifeCycleEventInterface
{
    public function __construct(Stopwatch $object)
    {
        parent::__construct($object);
    }
}
