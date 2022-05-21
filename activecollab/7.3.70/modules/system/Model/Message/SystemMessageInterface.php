<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Message;

interface SystemMessageInterface extends MessageInterface
{
    const SYSTEM_MESSAGE_USER_LEFT_CONVERSATION = UserLeftConversationMessage::class;
    const SYSTEM_MESSAGE_USER_RENAMED_CONVERSATION = UserRenamedConversationMessage::class;
    const SYSTEM_MESSAGE_USER_REMOVED_CONVERSATION = UserRemovedConversationMessage::class;
    const SYSTEM_MESSAGE_USER_INVITED_CONVERSATION = UserInvitedConversationMessage::class;

    const SYSTEM_MESSAGES = [
        self::SYSTEM_MESSAGE_USER_LEFT_CONVERSATION,
        self::SYSTEM_MESSAGE_USER_RENAMED_CONVERSATION,
        self::SYSTEM_MESSAGE_USER_REMOVED_CONVERSATION,
        self::SYSTEM_MESSAGE_USER_INVITED_CONVERSATION,
    ];
}
