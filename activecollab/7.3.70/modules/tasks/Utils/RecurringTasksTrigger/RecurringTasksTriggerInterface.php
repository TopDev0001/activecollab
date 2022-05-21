<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\RecurringTasksTrigger;

use DateValue;
use RecurringTask;
use Task;

interface RecurringTasksTriggerInterface
{
    /**
     * @return int[]|iterable
     */
    public function createForDay(DateValue $day): iterable;
    public function processRecurringTask(RecurringTask $recurring_task, DateValue $day): ?Task;
}
