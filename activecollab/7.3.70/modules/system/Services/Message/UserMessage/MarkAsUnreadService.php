<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Message\UserMessage;

use ActiveCollab\Module\System\Model\Message\UserMessage;
use ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler\UsersBadgeCountThrottlerInterface;
use User;

class MarkAsUnreadService extends UserMessageService implements MarkAsUnreadServiceInterface
{
    private $conversation_user_updater;
    private UsersBadgeCountThrottlerInterface $badge_count_throttler;

    public function __construct(
        callable $conversation_user_updater,
        UsersBadgeCountThrottlerInterface $badge_count_throttler
    ) {
        $this->conversation_user_updater = $conversation_user_updater;
        $this->badge_count_throttler = $badge_count_throttler;
    }

    public function markAsUnread(UserMessage $message, User $user): void
    {
        $conversation = $message->getConversation();
        $user_conversation = $conversation->getConversationUser($user);

        call_user_func(
            $this->conversation_user_updater,
            $user_conversation,
            [
                'new_messages_since' => $message->getCreatedOn()->advance(-1, false),
            ]
        );

        $conversation->touch();

        $this->badge_count_throttler->throttle([$user->getId()]);
    }
}
