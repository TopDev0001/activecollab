<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ReorderService;

use ActiveCollab\User\UserInterface;

interface OrderableDataManagerInterface
{
    public const POSITION_FILED = 'position';

    public function getItemsByPosition(?UserInterface $user = null): array;

    public function updatePositions(array $to_update): void;
}
