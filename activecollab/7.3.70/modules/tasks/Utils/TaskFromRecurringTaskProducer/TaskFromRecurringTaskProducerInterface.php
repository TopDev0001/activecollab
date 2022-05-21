<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskFromRecurringTaskProducer;

use DateValue;
use RecurringTask;
use Task;
use User;

interface TaskFromRecurringTaskProducerInterface
{
    public function produceManually(
        RecurringTask $recurring_task,
        User $created_by,
        DateValue $trigger_date,
        string $override_name = null
    ): Task;

    public function produceAutomatically(
        RecurringTask $recurring_task,
        DateValue $trigger_date
    ): Task;
}
