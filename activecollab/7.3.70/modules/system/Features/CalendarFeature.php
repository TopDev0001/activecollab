<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class CalendarFeature extends Feature implements CalendarFeatureInterface
{
    public function getName(): string
    {
        return CalendarFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return CalendarFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'calendar_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'calendar_enabled_lock';
    }
}
