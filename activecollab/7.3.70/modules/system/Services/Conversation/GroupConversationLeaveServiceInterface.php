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

interface GroupConversationLeaveServiceInterface extends ConversationServiceInterface
{
    public function leave(GroupConversationInterface $conversation, User $user): void;
}
