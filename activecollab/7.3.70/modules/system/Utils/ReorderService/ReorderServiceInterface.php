<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ReorderService;

use ActiveCollab\User\UserInterface;

interface ReorderServiceInterface
{
    public function reorder(array $changes, UserInterface $user): array;
    public function setDataManager(OrderableDataManagerInterface $data_manager): ReorderServiceInterface;
}
