<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\TimeRecordEvents;

use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\TrackingObjectEvents\TrackingObjectLifeCycleEvent;
use TimeRecord;

abstract class TimeRecordLifeCycleEvent extends TrackingObjectLifeCycleEvent implements TimeRecordLifeCycleEventInterface
{
    public function __construct(TimeRecord $object)
    {
        parent::__construct($object);
    }
}
