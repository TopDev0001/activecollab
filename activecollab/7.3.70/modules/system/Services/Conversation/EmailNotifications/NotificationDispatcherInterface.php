<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use IUser;

interface NotificationDispatcherInterface
{
    public function notify(IUser $user, UnreadConversationsDataInterface $notification_data): void;
}
