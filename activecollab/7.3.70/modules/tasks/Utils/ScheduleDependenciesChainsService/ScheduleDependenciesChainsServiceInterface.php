<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\ScheduleDependenciesChainsService;

use Task;

interface ScheduleDependenciesChainsServiceInterface
{
    public function makeSimulation(Task $task1, ?Task $task2 = null): array;
}
