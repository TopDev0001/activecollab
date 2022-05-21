<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\TrackingObjectEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use ITrackingObject;
use Member;
use User;
use Users;

abstract class TrackingObjectLifeCycleEvent extends DataObjectLifeCycleEvent implements TrackingObjectLifeCycleEventInterface
{
    public function __construct(ITrackingObject $object)
    {
        parent::__construct($object);
    }

    public function whoShouldBeNotified(): array
    {
        $user_ids = parent::whoShouldBeNotified();

        $power_user_ids = Users::findIdsByType(
            Member::class,
            $user_ids,
            function ($id, $type, $custom_permissions) {
                return in_array(User::CAN_MANAGE_PROJECTS, $custom_permissions);
            }
        );

        if (is_array($power_user_ids)) {
            $user_ids = array_unique(array_merge($user_ids, $power_user_ids));
        }

        return $user_ids;
    }
}
