<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use DateTimeValue;
use IUser;

interface ConversationNotificationDataFactoryInterface
{
    public function produceDataForUser(IUser $user, DateTimeValue $from): UnreadConversationsDataInterface;
}
