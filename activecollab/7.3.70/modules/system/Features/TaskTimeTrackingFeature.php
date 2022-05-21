<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class TaskTimeTrackingFeature extends Feature implements TaskTimeTrackingFeatureInterface
{
    public function getName(): string
    {
        return TaskTimeTrackingFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return TaskTimeTrackingFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'task_time_tracking_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'task_time_tracking_enabled_lock';
    }
}
