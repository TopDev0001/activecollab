<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageUpdatedEvent;
use ActiveCollab\Module\System\Model\Conversation\OwnConversation;
use ActiveCollab\Module\System\Model\Message\UserMessage;

class Messages extends BaseMessages
{
    public static function prepareRelativeCursorCollection(
        string $collection_name,
        User $user
    ): RelativeCursorModelCollection
    {
        if ($user->isClient()) {
            throw new InvalidParamError('user', $user, '$user cannot be client.');
        }

        if (str_starts_with($collection_name, 'conversation_messages')) {
            return self::prepareConversationMessagesCollection($collection_name, $user);
        } else {
            throw new RuntimeException("Collection name '$collection_name' does not exist.");
        }
    }

    private static function prepareConversationMessagesCollection(string $collection_name, ?User $user)
    {
        $bits = explode('_', $collection_name);
        $conversation_id = (int) array_pop($bits);

        $conversation = Conversations::findById($conversation_id);

        if (empty($conversation)) {
            throw new ImpossibleCollectionError("Conversation #{$conversation_id} not found.");
        }

        if ($user && !$conversation->isMember($user)) {
            throw new ImpossibleCollectionError("User #{$user->getId()} is not a member of this conversation.");
        }

        $collection = parent::prepareRelativeCursorCollection($collection_name, $user);

        $collection->setCursorField('order_id');

        if (isset($_GET['cursor'], $_GET['last_id'])) {
            $collection->setCursor((int) $_GET['cursor']);
            $collection->setLastId((int) $_GET['last_id']);
        }

        $collection->setConditions('conversation_id = ?', $conversation->getId());

        if (isset($_GET['limit'])) {
            $collection->setLimit((int) $_GET['limit']);
        }

        $collection->setPreExecuteCallback(function ($ids) {
            if ($ids && is_foreachable($ids)) {
                Attachments::preloadDetailsByParents(UserMessage::class, $ids);
                Reactions::preloadDetailsByParents(UserMessage::class, $ids);
            }
        });

        return $collection;
    }

    private static array $details_by_conversation = [];

    public static function getByConversation(Conversation $conversation): array
    {
        if (isset(self::$details_by_conversation[$conversation->getId()])) {
            return self::$details_by_conversation[$conversation->getId()];
        } else {
            $messages = self::find(
                [
                    'conditions' => [
                        'conversation_id = ?',
                        $conversation->getId(),
                    ],
                    'order' => 'order_id DESC, id DESC',
                    'limit' => 20,
                ]
            );

            return $messages ? $messages->toArray() : [];
        }
    }

    public static function preloadDetailsByConversationIds(array $conversation_ids): void
    {
        $first_id = array_shift($conversation_ids);

        $query = "(SELECT id
                         FROM messages
                         WHERE conversation_id = {$first_id}
                         ORDER BY order_id DESC, id DESC
                         LIMIT 20)";

        foreach ($conversation_ids as $conversation_id) {
            $query .= " UNION ALL (SELECT id
                         FROM messages
                         WHERE conversation_id = {$conversation_id}
                         ORDER BY order_id DESC, id DESC
                         LIMIT 20)";
        }

        $ids = DB::executeFirstColumn($query);

        if ($ids) {
            Attachments::preloadDetailsByParents(UserMessage::class, $ids);

            $messages = Messages::findByIds($ids);

            foreach ($messages as $message) {
                if (!isset(self::$details_by_conversation[$message->getConversationId()])) {
                    self::$details_by_conversation[$message->getConversationId()] = [];
                }

                self::$details_by_conversation[$message->getConversationId()][] = $message->jsonSerialize();
            }
        }

        if ($zeros = array_diff([$first_id, ...$conversation_ids], array_keys(self::$details_by_conversation))) {
            foreach ($zeros as $conversation_with_no_message) {
                self::$details_by_conversation[$conversation_with_no_message] = [];
            }
        }
    }

    public static function &update(DataObject &$instance, array $attributes, bool $save = true): Message
    {
        $message = parent::update(
            $instance,
            array_merge(
                $attributes,
                [
                    'changed_on' => new DateTimeValue(),
                ]
            ),
            $save
        );

        DataObjectPool::announce(new MessageUpdatedEvent($message));

        $message->getConversation()->touch();

        return $message;
    }

    public static function getUnreadMessagesCountForUser(User $user): int
    {
        return (int) DB::executeFirstCell(
            'SELECT COUNT(m.id) 
            FROM messages m
            LEFT JOIN conversation_users cu ON m.conversation_id = cu.conversation_id AND cu.user_id = ?
            LEFT JOIN conversations c ON c.id = cu.conversation_id
            WHERE m.created_by_id != ? AND cu.is_muted = ? AND c.type != ?
              AND (cu.new_messages_since < m.created_on OR cu.new_messages_since IS NULL)',
            $user->getId(),
            $user->getId(),
            false,
            OwnConversation::class
        );
    }
}
