<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\DatesRescheduleSimulator;

use DateValue;
use Task;

interface DatesRescheduleSimulatorInterface
{
    public function simulateReschedule(Task $task, DateValue $due_on): array;
}
