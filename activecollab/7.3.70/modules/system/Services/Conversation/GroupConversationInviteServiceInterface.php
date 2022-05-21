<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation;

use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use User;

interface GroupConversationInviteServiceInterface extends ConversationServiceInterface
{
    public function invite(
        GroupConversationInterface $conversation,
        User $user,
        array $user_ids
    ): GroupConversationInterface;
}
