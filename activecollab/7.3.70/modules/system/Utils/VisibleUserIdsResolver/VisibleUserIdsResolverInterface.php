<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\VisibleUserIdsResolver;

use IMembers;
use User;

interface VisibleUserIdsResolverInterface
{
    public function getAll(
        User $user,
        int $min_state = STATE_VISIBLE,
        bool $use_cache = true
    ): array;

    public function getInContext(
        User $user,
        IMembers $context,
        int $min_state = STATE_VISIBLE,
        bool $use_cache = true
    ): array;
}
