<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GeneralConversation;
use ActiveCollab\Module\System\Model\Conversation\OneToOneConversation;
use ActiveCollab\Module\System\Model\Conversation\OwnConversation;
use ActiveCollab\Module\System\Model\Message\UserMessage;
use ActiveCollab\Module\System\SystemModule;
use Conversations;
use DataObjectPool;
use DateTimeValue;
use DB;
use IUser;
use ReflectionClass;

class ConversationNotificationDataFactory implements ConversationNotificationDataFactoryInterface
{
    private string $system_path;

    public function __construct(string $system_path)
    {
        $this->system_path = $system_path;
    }

    public function produceDataForUser(IUser $user, DateTimeValue $from): UnreadConversationsDataInterface
    {
        $conversation_ids_with_new_messages = (array) DB::executeFirstColumn(
            'SELECT cu.conversation_id
            FROM conversation_users cu
            LEFT JOIN conversations c ON c.id = cu.conversation_id
            WHERE cu.user_id = ?
                AND cu.is_muted = false
                AND c.last_message_on IS NOT NULL
                AND c.last_message_on > ?
                AND (c.last_message_on > cu.new_messages_since OR cu.new_messages_since IS NULL)
                AND c.type != ?',
            $user->getId(),
            $from,
            OwnConversation::class
        );

        $total = 0;
        $breakdown = [];

        if (empty($conversation_ids_with_new_messages)) {
            return new UnreadConversationsData($total, $breakdown);
        }

        $first_id = array_shift($conversation_ids_with_new_messages);
        $sql_params = [UserMessage::class];

        $query = '(SELECT c.id, c.type, c.name, cu.new_messages_since, COUNT(m.id) as count
                    FROM messages m
                    LEFT JOIN conversations c ON c.id = m.conversation_id
                    LEFT JOIN conversation_users cu ON cu.conversation_id = c.id AND cu.user_id = %s
                    WHERE m.conversation_id = %s
                        AND m.type = ?
                        AND m.created_by_id != %s
                        AND (m.created_on > cu.new_messages_since OR cu.new_messages_since IS NULL)
                    GROUP BY c.id)';

        $sql = sprintf($query, $user->getId(), $first_id, $user->getId());

        foreach ($conversation_ids_with_new_messages as $conversation_id) {
            $sql .= sprintf(
                ' UNION ALL %s',
                sprintf($query, $user->getId(), $conversation_id, $user->getId())
            );
            $sql_params[] = UserMessage::class;
        }

        $results = DB::execute($sql, ...$sql_params);

        if (!$results) {
            return new UnreadConversationsData($total, $breakdown);
        }

        foreach ($results as $result) {
            $total += (int) $result['count'];

            $breakdown[] = [
                'type' => (new ReflectionClass($result['type']))->getShortName(),
                'count' => (int) $result['count'],
                'name' => $this->getConversationNameFromResultFor($user, $result),
                'avatar_url' => $this->getConversationAvatarUrlFromResultFor($user, $result),
            ];
        }

        return new UnreadConversationsData($total, $breakdown);
    }

    private function getConversationNameFromResultFor(IUser $user, array $result): string
    {
        if ($result['name']) {
            return $result['name'];
        }

        if ($result['type'] === GeneralConversation::class) {
            return 'General';
        }

        if ($result['type'] === OneToOneConversation::class) {
            // return ime usera iz user objekta getDisplayName
            $conversation = DataObjectPool::get($result['type'], $result['id']);
            if ($conversation instanceof OneToOneConversation) {
                $second_user = $this->getOneToOneConversationUserFor($user, $conversation);
                if ($second_user instanceof IUser) {
                    return $second_user->getDisplayName();
                }
            }
        }

        /** @var ConversationInterface $conversation */
        $conversation = Conversations::findById($result['id']);

        return $conversation->getDisplayName($user);
    }

    private function getConversationAvatarUrlFromResultFor(IUser $user, array $result): string
    {
        $conversation = DataObjectPool::get($result['type'], $result['id']);

        if ($conversation instanceof OneToOneConversation) {
            $second_user = $this->getOneToOneConversationUserFor($user, $conversation);
            if ($second_user instanceof IUser) {
                return $second_user->getAvatarUrl(36);
            }
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($this->system_path . '/resources/people.png'));
    }

    private function getOneToOneConversationUserFor($user, $conversation)
    {
        $members = $conversation->getMembers();

        $filtered_members = array_filter(
            $members->toArray(),
            function ($member) use ($user) {
                return $member->getId() !== $user->getId();
            }
        );

        return reset($filtered_members);
    }
}
