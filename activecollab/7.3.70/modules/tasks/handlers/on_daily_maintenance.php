<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\Tasks\Features\RecurringTasksFeatureInterface;
use ActiveCollab\Module\Tasks\Utils\RecurringTasksTrigger\RecurringTasksTriggerInterface;
use Angie\Utils\SystemDateResolver\SystemDateResolverInterface;

function tasks_handle_on_daily_maintenance()
{
    if (AngieApplication::isOnDemand()
        && AngieApplication::accountSettings()->getAccountStatus()->isSuspended()
    ) {
        return;
    }

    $system_date = AngieApplication::getContainer()
        ->get(SystemDateResolverInterface::class)
            ->getSystemDate();

    if (AngieApplication::featureFactory()->makeFeature(RecurringTasksFeatureInterface::NAME)->isEnabled()) {
        AngieApplication::getContainer()
            ->get(RecurringTasksTriggerInterface::class)
                ->createForDay($system_date);
    }
}
