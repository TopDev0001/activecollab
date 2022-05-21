<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare (strict_types=1);

namespace ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\UserInternalRateEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use DataObject;
use UserInternalRate;

interface UserInternalRateLifeCycleEventInterface extends DataObjectLifeCycleEventInterface
{
    /**
     * @return UserInternalRate
     */
    public function getObject(): DataObject;
}
