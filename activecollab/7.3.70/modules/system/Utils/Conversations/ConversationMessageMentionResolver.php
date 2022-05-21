<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Utils\ActiveCollabCliCommandExecutor\ActiveCollabCliCommandExecutorInterface;
use Message;
use RealTimeIntegrationInterface;

class ConversationMessageMentionResolver implements MessageMentionResolverInterface
{
    private ActiveCollabCliCommandExecutorInterface $command_executor;

    public function __construct(
        ActiveCollabCliCommandExecutorInterface $command_executor
    ) {
        $this->command_executor = $command_executor;
    }

    public function resolve(Message $message): void
    {
        if (empty($message->getNewMentions())) {
            return;
        }

        $this->command_executor->execute(
            'chat:unmute_mentioned_users',
            [
                $message->getId(),
                implode(' ', $message->getNewMentions()),
            ],
            RealTimeIntegrationInterface::CHAT_JOBS_QUEUE_CHANNEL,
        );
    }
}
