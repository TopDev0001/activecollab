<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Utils\Dependency\DependencyInterface;

interface ITaskDependencies extends DependencyInterface
{
    public function getParentsIds(): array;
    public function getChildrenIds(): array;
    public function getOpenDependencies(bool $use_cache = true): array;

    public function &cloneDependenciesTo(ITaskDependencies $to): ITaskDependencies;
}
