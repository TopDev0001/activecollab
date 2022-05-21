<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler;

use DateValue;
use Task;

interface TaskDatesCorrectorInterface
{
    public function correctDates(Task $task, DateValue &$start_on, DateValue &$due_on): void;
}
