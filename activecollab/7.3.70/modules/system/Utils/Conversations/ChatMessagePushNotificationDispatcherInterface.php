<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Model\Message\UserMessage;

interface ChatMessagePushNotificationDispatcherInterface
{
    public function dispatch(UserMessage $message, bool $badge_count_only = false): void;
}
