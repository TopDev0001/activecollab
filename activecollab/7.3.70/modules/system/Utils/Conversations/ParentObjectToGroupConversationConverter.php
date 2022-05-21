<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use ActiveCollab\Module\System\Model\Conversation\ParentObjectConversation;
use Conversations;
use ConversationUsers;
use DataObject;
use DB;
use IMembers;
use LogicException;

class ParentObjectToGroupConversationConverter implements ParentObjectToGroupConversationConverterInterface
{
    public function convert(ParentObjectConversation $conversation): void
    {
        /** @var DataObject|IMembers $parent */
        $parent = $conversation->getParent();

        if (!$parent) {
            throw new LogicException("There is no parent object for conversation #{$conversation->getId()}");
        }

        $parent_member_ids = $parent->getMemberIds();
        $conversation_users_ids = (array) DB::executeFirstColumn(
            'SELECT DISTINCT user_id FROM conversation_users WHERE conversation_id = ?',
            $conversation->getId()
        );

        // ensure that all parent's members become conversation members
        if (count($parent_member_ids) > count($conversation_users_ids)) {
            $new_records = [];
            foreach (array_diff($parent_member_ids, $conversation_users_ids) as $user_id) {
                $new_records[] = [
                    'conversation_id' => $conversation->getId(),
                    'user_id' => $user_id,
                ];
            }

            ConversationUsers::createMany($new_records);
        }

        // set all members to become admins of the group
        DB::execute(
            'UPDATE conversation_users SET is_admin = ? WHERE conversation_id = ?',
            true,
            $conversation->getId()
        );

        Conversations::update(
            $conversation,
            [
                'type' => GroupConversation::class,
                'parent_type' => null,
                'parent_id' => null,
            ]
        );
    }
}
