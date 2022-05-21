<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\TrackingBillableStatusResolver;

use ITracking;
use ITrackingObject;
use IUser;

interface TrackingBillableStatusResolverInterface
{
    public function getBillabeStatus(IUser $user, ITracking $tracking_on, int $billable_status): int;

    public function getBillabeStatusForTrackingObject(
        IUser $user,
        ITrackingObject $tracking_object,
        int $billable_status
    ): int;
}
