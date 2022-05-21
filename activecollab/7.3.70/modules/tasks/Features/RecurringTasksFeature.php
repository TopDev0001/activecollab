<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Features;

use Angie\Features\Feature;

class RecurringTasksFeature extends Feature implements RecurringTasksFeatureInterface
{
    public function getName(): string
    {
        return RecurringTasksFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return RecurringTasksFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'recurring_tasks_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'recurring_tasks_enabled_lock';
    }
}
