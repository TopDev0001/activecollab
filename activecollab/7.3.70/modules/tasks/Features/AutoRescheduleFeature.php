<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Features;

use Angie\Features\Feature;

class AutoRescheduleFeature extends Feature implements AutoRescheduleFeatureInterface
{
    public function getName(): string
    {
        return AutoRescheduleFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return AutoRescheduleFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'auto_reschedule_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'auto_reschedule_enabled_lock';
    }
}
