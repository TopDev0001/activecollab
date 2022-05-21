<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use DateTimeValue;

interface UsersToNotifyAboutUnreadMessagesResolverInterface
{
    const MESSAGES_OLDER_THEN_90_MINUTES = 5400; // 90 minutes
    const USERS_INACTIVE_FOR_30_MINUTES = 1800; // 30 minutes

    public function getUserIds(DateTimeValue $current_time): array;
}
