<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\UsersDisplayNameResolver;

use IMembers;
use IUser;

interface UsersDisplayNameResolverInterface
{
    public function getNameFor(IMembers $context, ?IUser $for = null, ?string $default = null): ?string;
}
