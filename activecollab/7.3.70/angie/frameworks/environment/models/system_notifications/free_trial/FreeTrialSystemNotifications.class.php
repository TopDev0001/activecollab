<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class FreeTrialSystemNotifications extends SystemNotifications
{
    public static function getType()
    {
        return FreeTrialSystemNotification::class;
    }

    public static function shouldBeRaised(): bool
    {
        $account_status = AngieApplication::accountSettings()->getAccountStatus();

        if (AngieApplication::isOnDemand() && $account_status->isTrial()) {
            if ($account_status->getDaysToStatusExpiration() <= 4) {
                self::clearNotifications(); // remove notifications of this type and show the new ones

                return true;
            }

            return false;
        }

        return false;
    }
}
