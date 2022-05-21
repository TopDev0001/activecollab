<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Features;

use Angie\Features\Feature;

class TimelineFeature extends Feature implements TimelineFeatureInterface
{
    public function getName(): string
    {
        return TimelineFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return TimelineFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'timeline_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'timeline_enabled_lock';
    }
}
