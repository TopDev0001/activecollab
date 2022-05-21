<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class SupportExpirationSystemNotifications extends SystemNotifications
{
    public static function getType()
    {
        return SupportExpirationSystemNotification::class;
    }

    public static function shouldBeRaised(): bool
    {
        $expires_on = DateValue::makeFromTimestamp(AngieApplication::autoUpgrade()->getSupportSubscriptionExpiresOn());

        if (empty($expires_on)) {
            $expires_on = new DateValue();
        }

        $today = new DateValue();
        $days_between = $today->daysBetween($expires_on);

        return in_array($days_between, [30, 15, 7, 3, 2, 1]);
    }
}
