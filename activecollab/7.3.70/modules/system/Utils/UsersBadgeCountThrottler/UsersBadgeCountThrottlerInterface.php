<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler;

interface UsersBadgeCountThrottlerInterface
{
    public function throttle(array $user_ids): void;
}
