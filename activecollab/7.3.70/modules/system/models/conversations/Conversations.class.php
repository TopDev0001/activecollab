<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationEvents\ConversationUpdatedEvent;
use ActiveCollab\Module\System\Model\Conversation\GeneralConversation;
use ActiveCollab\Module\System\Model\Conversation\GroupConversation;
use ActiveCollab\Module\System\Model\Conversation\OwnConversation;
use ActiveCollab\Module\System\Model\Conversation\ParentObjectConversation;
use ActiveCollab\Module\System\Model\Conversation\SmartConversation;

class Conversations extends BaseConversations
{
    public static function prepareCollection(string $collection_name, $user)
    {
        if ($user->isClient()) {
            throw new InvalidParamError('user', $user, '$user cannot be client.');
        }

        if (str_starts_with($collection_name, 'user_conversations')) {
            return self::prepareUserConversationsCollection($collection_name, $user);
        } else {
            throw new RuntimeException("Collection name '$collection_name' does not exist.");
        }
    }

    private static function prepareUserConversationsCollection(string $collection_name, User $user)
    {
        $smart_conversation_ids = DB::executeFirstColumn(
            'SELECT DISTINCT c.id
                 FROM conversations c
                 WHERE c.id IN (?)',
            self::getSmartConversationIdsForUser($user)
        ) ?? [0];

        $custom_conversation_ids = DB::executeFirstColumn(
            'SELECT DISTINCT cu.conversation_id
                 FROM conversation_users cu
                 WHERE cu.user_id = ? AND cu.conversation_id NOT IN (?)',
            $user->getId(),
            $smart_conversation_ids
        ) ?? [0];

        $collection = parent::prepareCollection($collection_name, $user);
        $collection->setConditions(
            'id IN (?)',
            array_merge(
                $custom_conversation_ids,
                $smart_conversation_ids
            )
        );

        $collection->setPreExecuteCallback(function ($ids) use ($user, $custom_conversation_ids) {
            if ($ids && is_foreachable($ids)) {
                Users::preloadMemberIdsFromConnectionTable(
                    Team::class,
                    $user->getTeamIds(),
                    'team_users',
                    'team_id',
                    'user_id',
                    false
                );
                Users::preloadMemberIdsFromConnectionTable(
                    Conversation::class,
                    $custom_conversation_ids,
                    'conversation_users',
                    'conversation_id',
                    'user_id',
                    false
                );
                Users::preloadMemberIdsFromConnectionTable(
                    GroupConversation::class,
                    $custom_conversation_ids,
                    'conversation_users',
                    'conversation_id',
                    'user_id',
                    false,
                    DB::prepare(' AND c.is_admin = ?', true)
                );
                Messages::preloadDetailsByConversationIds($ids);
            }
        });

        return $collection;
    }

    private static function getSmartConversationIdsForUser(User $user): array
    {
        $ids = [0];

        if ($user->isEmployee() && $general_conversation = self::getGeneralConversation()) {
            $ids[] = $general_conversation->getId();
        }

        if ($parent_object_conversations = self::getParentObjectConversations($user)) {
            foreach ($parent_object_conversations as $conversation) {
                $ids[] = $conversation->getId();
            }
        }

        return $ids;
    }

    public static function getGeneralConversation(): ?GeneralConversation
    {
        return parent::findOneBy('type', GeneralConversation::class);
    }

    public static function getParentObjectConversations(User $user): ?DBResult
    {
        return self::find(
            [
                'conditions' => [
                    'type = ? AND parent_type = ? AND parent_id IN (?)',
                    ParentObjectConversation::class,
                    Team::class,
                    $user->getTeamIds(),
                ],
            ]
        );
    }

    public static function checkObjectEtag($id, $hash): bool
    {
        $updated_on = DB::executeFirstCell('SELECT updated_on FROM ' . static::getTableName() . ' WHERE id = ?', $id);

        $conversation = self::findById($id);

        return $hash === sha1(
                sprintf(
                    '%s%s%s',
                    APPLICATION_UNIQUE_KEY,
                    $updated_on,
                    $conversation instanceof SmartConversation ? $conversation->getExtendedTimestampValue() : ''
                )
            );
    }

    public static function getUserIdsWithUnreadMessages(DateTimeValue $from): array
    {
        return (array) DB::executeFirstColumn(
                    'SELECT cu.user_id
                    FROM conversation_users cu
                    LEFT JOIN conversations c ON c.id = cu.conversation_id
                    WHERE cu.is_muted = false
                      AND c.last_message_on IS NOT NULL
                      AND (c.last_message_on > cu.new_messages_since OR cu.new_messages_since IS NULL)
                      AND c.last_message_on > ?
                      AND c.type != ?
                    GROUP BY cu.user_id',
            $from,
            OwnConversation::class
        );
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): Conversation
    {
        $conversation = parent::update($instance, $attributes, $save);

        // there is some issue when 'type' field is updated, object still has previous type value
        // because of that, we need to reload it
        if (array_key_exists('type', $attributes)) {
            $conversation = DataObjectPool::reload(get_class($conversation), $conversation->getId());
        }

        DataObjectPool::announce(new ConversationUpdatedEvent($conversation));

        return $conversation;
    }

    public static function getMutedMemberIdsFromConversation(Conversation $conversation): array
    {
        return (array) DB::executeFirstColumn(
            'SELECT cu.user_id 
            FROM conversation_users cu 
            WHERE cu.conversation_id = ? AND cu.is_muted = ?',
            $conversation->getId(),
            true
        );
    }
}
