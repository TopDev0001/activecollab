<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\PushNotification;

use User;

interface PushNotificationScheduleMatcherInterface
{
    public function match(array $user_ids): array;

    public function matchForUser(User $user): bool;
}
