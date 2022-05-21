<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ActiveCollab\Module\System\Model\Message\SystemMessageInterface;
use ActiveCollab\Module\System\Model\Message\UserMessageInterface;
use User;

interface ConversationMessageFactoryInterface
{
    public function createUserMessage(
        ConversationInterface $conversation,
        User $user,
        string $body,
        array $attachments = [],
        ?int $order_id = null
    ): UserMessageInterface;

    public function createSystemMessage(
        string $system_message,
        GroupConversationInterface $conversation,
        User $user,
        array $additional_data = []
    ): SystemMessageInterface;
}
