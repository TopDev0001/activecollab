<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\CustomConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use ActiveCollab\Module\System\Model\Conversation\ParentObjectConversation;
use DataObject;
use User;

interface ConversationResolverInterface
{
    public function getConversation(User $user, DataObject $object): ?ConversationInterface;

    public function getCustomConversation(
        array $user_ids,
        string $type = GroupConversation::class
    ): ?CustomConversationInterface;

    public function getParentObjectConversation(DataObject $object): ?ParentObjectConversation;
}
