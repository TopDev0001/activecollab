<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Features;

use Angie\Features\FeatureInterface;

interface TaskDependenciesFeatureInterface extends FeatureInterface
{
    const NAME = 'task_dependencies';
    const VERBOSE_NAME = 'Task Dependencies';
}
