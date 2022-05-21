<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\DirectAcyclicGraphFactory;

interface DirectAcyclicGraphFactoryInterface
{
    public function createStructure(array $parent_child_dependencies, bool $from_child_to_parent = false): array;
}
