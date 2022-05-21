<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\InactiveUsersResolver;

use DateTimeValue;
use IUser;

interface InactiveUsersResolverInterface
{
    public function getInactiveUsersIds(DateTimeValue $current_time, int $inactive_for_seconds): array;

    public function isUserInactive(IUser $user, int $inactive_for_seconds, DateTimeValue $current_time = null): bool;
}
