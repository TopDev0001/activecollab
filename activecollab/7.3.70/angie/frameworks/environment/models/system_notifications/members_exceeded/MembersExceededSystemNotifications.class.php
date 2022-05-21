<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MembersExceededSystemNotifications extends SystemNotifications
{
    public static function getType()
    {
        return MembersExceededSystemNotification::class;
    }

    public static function shouldBeRaised(): bool
    {
        $max_members = AngieApplication::accountSettings()->getAccountLimitations()->getMaxMembers();

        if (empty($max_members)) {
            return false;
        }

        return AngieApplication::isOnDemand() && Users::countActiveUsers() > $max_members;
    }
}
