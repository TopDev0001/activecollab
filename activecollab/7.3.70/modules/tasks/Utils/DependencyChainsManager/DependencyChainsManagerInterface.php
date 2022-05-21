<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\DependencyChainsManager;

use Task;

interface DependencyChainsManagerInterface
{
    public function getParentToChildChains(Task $task, bool $between_scheduled = true): array;

    public function getChildToParentChains(Task $task, bool $between_scheduled = true): array;
}
