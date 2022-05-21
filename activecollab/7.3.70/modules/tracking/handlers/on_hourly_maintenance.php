<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\Utils\BudgetNotificationsMaintenanceInterface;
use ActiveCollab\Module\Tracking\Utils\StopwatchesMaintenance\StopwatchesMaintenanceInterface;

function tracking_handle_on_hourly_maintenance()
{
    /** @var StopwatchesMaintenanceInterface $maintainer */
    $maintainer = AngieApplication::getContainer()
        ->get(StopwatchesMaintenanceInterface::class)
            ->getForMaintenance();

    if ($maintainer->shouldRun()) {
        $maintainer->run();
    }

    AngieApplication::getContainer()
        ->get(BudgetNotificationsMaintenanceInterface::class)
            ->run();
}
