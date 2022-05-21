<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation;

use DateTimeValue;
use IUser;

interface NotifyUserAboutUnreadMessagesServiceInterface
{
    public function notify(IUser $user, DateTimeValue $current_time): void;
}
