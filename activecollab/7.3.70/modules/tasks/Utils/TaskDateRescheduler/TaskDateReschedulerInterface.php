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
use User;

interface TaskDateReschedulerInterface
{
    public function updateInitialTaskDate(Task $task, DateValue $start_on, DateValue $due_on): Task;

    public function isSimulationIdentical(array $old_simulation, array $new_simulation): bool;

    public function updateSimulationTaskDates(Task $initial_task, array $new_simulation, User $by): array;
}
