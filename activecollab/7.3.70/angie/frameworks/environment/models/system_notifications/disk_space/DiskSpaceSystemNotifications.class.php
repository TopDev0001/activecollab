<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Storage\OveruseResolver\StorageOveruseResolverInterface;

class DiskSpaceSystemNotifications extends SystemNotifications
{
    public static function getType()
    {
        return DiskSpaceSystemNotification::class;
    }

    public static function shouldBeRaised(): bool
    {
        return AngieApplication::isOnDemand()
            && AngieApplication::getContainer()->get(StorageOveruseResolverInterface::class)->isDiskFull(true);
    }
}
