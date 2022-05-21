<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Model\Message\UserMessage;
use ActiveCollab\Module\System\Utils\ActiveCollabCliCommandExecutor\ActiveCollabCliCommandExecutorInterface;
use Angie\FeatureFlags\FeatureFlagsInterface;
use PushNotificationChannel;

class ChatMessagePushNotificationDispatcher implements ChatMessagePushNotificationDispatcherInterface
{
    private FeatureFlagsInterface $feature_flags;
    private ActiveCollabCliCommandExecutorInterface $command_executor;

    public function __construct(
        FeatureFlagsInterface $feature_flags,
        ActiveCollabCliCommandExecutorInterface $command_executor
    ) {
        $this->feature_flags = $feature_flags;
        $this->command_executor = $command_executor;
    }

    public function dispatch(UserMessage $message, bool $badge_count_only = false): void
    {
        if (!$this->feature_flags->isEnabled('push_notifications_for_chat')) {
            return;
        }

        if (!$badge_count_only) {
            $this->command_executor->execute(
                'chat:send_push_notification',
                [
                    $message->getId(),
                ],
                PushNotificationChannel::CHANNEL_NAME,
            );
        }

        $this->command_executor->execute(
            'chat:send_badge_count_for_conversation_users',
            [
                $message->getConversationId(),
                $message->getCreatedById(),
            ],
            PushNotificationChannel::CHANNEL_NAME,
        );
    }
}
