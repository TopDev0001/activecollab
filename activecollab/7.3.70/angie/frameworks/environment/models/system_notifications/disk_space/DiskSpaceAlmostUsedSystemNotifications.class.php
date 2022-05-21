<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class DiskSpaceAlmostUsedSystemNotifications extends SystemNotifications
{
    public static function getType()
    {
        return DiskSpaceAlmostUsedSystemNotification::class;
    }

    public static function shouldBeRaised(): bool
    {
        $usage_percent = floor(AngieApplication::accountSettings()->getCurrentUsage()->getUsedDiskSpacePercent());

        if (AngieApplication::isOnDemand() && $usage_percent >= 95 && $usage_percent < 100 && !self::notificationForPercentExists($usage_percent)) {
            return true;
        }

        if ($usage_percent < 95) {
            self::clearNotifications();
        }

        return false;
    }

    private static function notificationForPercentExists(int $percent): bool
    {
        /** @var DiskSpaceSystemNotification[] $existing_notifications */
        $existing_notifications = DiskSpaceAlmostUsedSystemNotifications::find();

        foreach ($existing_notifications as $notification) {
            $rounded_usage_percent = $notification->getAdditionalProperty('rounded_percent_usage');
            if ($rounded_usage_percent && $rounded_usage_percent == $percent) {
                return true;
            }
        }

        return false;
    }
}
