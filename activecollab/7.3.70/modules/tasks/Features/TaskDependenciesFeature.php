<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Features;

use Angie\Features\Feature;

class TaskDependenciesFeature extends Feature implements TaskDependenciesFeatureInterface
{
    public function getName(): string
    {
        return TaskDependenciesFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return TaskDependenciesFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'task_dependencies_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'task_dependencies_enabled_lock';
    }
}
