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
use ActiveCollab\Module\System\Model\Conversation\OneToOneConversation;
use ActiveCollab\Module\System\Model\Conversation\OwnConversation;
use ActiveCollab\Module\System\Model\Conversation\ParentObjectConversation;
use Conversations;
use DataObject;
use DB;
use IMembers;
use LogicException;
use User;

class ConversationResolver implements ConversationResolverInterface
{
    public function getConversation(User $user, DataObject $object): ?ConversationInterface
    {
        $conversation_id = null;

        if ($object instanceof User) {
            $conversation = $this->getCustomConversation(
                [$user->getId(), $object->getId()],
                $user->getId() !== $object->getId()
                    ? OneToOneConversation::class
                    : OwnConversation::class
            );
            $conversation_id = $conversation ? $conversation->getId() : null;
        } elseif ($object instanceof IMembers) {
            $conversation_id = $this->smartConversation($user, $object);
        }

        return $conversation_id
            ? Conversations::findById($conversation_id)
            : null;
    }

    public function getCustomConversation(
        array $user_ids,
        string $type = GroupConversation::class
    ): ?CustomConversationInterface
    {
        $user_ids = array_unique(array_filter($user_ids, 'is_int'));

        $conversation_id = $this->getConversationIdByUserIds($user_ids, $type);

        return $conversation_id ? Conversations::findById($conversation_id) : null;
    }

    private function smartConversation(User $user, IMembers $object)
    {
        if (!in_array($user->getId(), $object->getMemberIds())) {
            throw new LogicException("Smart conversation isn't accessible for user #{$user->getId()}.");
        }

        return DB::executeFirstCell(
            'SELECT id FROM conversations WHERE parent_type = ? AND parent_id = ?',
            get_class($object),
            $object->getId()
        );
    }

    public function getConversationIdByUserIds(array $user_ids, string $conversation_type): ?int
    {
        if (!count($user_ids)) {
            return null;
        }

        $query = 'SELECT c.id FROM conversations c WHERE c.type = ? AND ';

        foreach ($user_ids as $user_id) {
            $query .= sprintf(
                'EXISTS (SELECT 1 FROM conversation_users cu WHERE cu.conversation_id = c.id AND cu.user_id = %s) AND ',
                $user_id
            );
        }

        $query .= sprintf(
            'NOT EXISTS (SELECT 1 FROM conversation_users cu WHERE cu.conversation_id = c.id AND cu.user_id NOT IN (%s))',
            implode(',', $user_ids)
        );

        return DB::executeFirstCell($query, $conversation_type);
    }

    public function getParentObjectConversation(DataObject $object): ?ParentObjectConversation
    {
        return Conversations::findOne(
            [
                'conditions' => [
                    'type = ? AND parent_type = ? AND parent_id = ?',
                    ParentObjectConversation::class,
                    get_class($object),
                    $object->getId(),
                ],
            ]
        );
    }
}
