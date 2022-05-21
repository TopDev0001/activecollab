<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class UpgradeAvailableSystemNotifications extends SystemNotifications
{
    public static function getType()
    {
        return UpgradeAvailableSystemNotification::class;
    }

    public static function shouldBeRaised(): bool
    {
        if (!AngieApplication::isOnDemand()) {
            return AngieApplication::getVersion() != AngieApplication::autoUpgrade()->getLatestAvailableVersion();
        }

        return false;
    }
}
