<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\VisibleCompanyIdsResolver;

use User;

interface VisibleCompanyIdsResolverInterface
{
    public function getAll(User $user, bool $use_cache = true): array;
}
