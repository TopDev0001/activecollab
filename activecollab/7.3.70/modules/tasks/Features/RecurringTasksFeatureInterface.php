<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Features;

use Angie\Features\FeatureInterface;

interface RecurringTasksFeatureInterface extends FeatureInterface
{
    const NAME = 'recurring_tasks';
    const VERBOSE_NAME = 'Recurring Tasks';
}
