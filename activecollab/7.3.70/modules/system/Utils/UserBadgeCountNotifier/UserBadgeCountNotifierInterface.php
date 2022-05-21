<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\UserBadgeCountNotifier;

use User;

interface UserBadgeCountNotifierInterface
{
    public function notify(User $user): void;
}
