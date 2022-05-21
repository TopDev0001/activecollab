<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\AvailabilityRecordEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use AvailabilityRecord;

abstract class AvailabilityRecordLifeCycleEvent extends DataObjectLifeCycleEvent implements AvailabilityRecordLifeCycleEventInterface
{
    public function __construct(AvailabilityRecord $object)
    {
        parent::__construct($object);
    }
}
