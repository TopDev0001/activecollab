<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use DBConnection;

final class ChatCollection extends Collection
{
    private DBConnection $connection;

    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getValueFor(DateValue $date): ResultInterface
    {
        [
            $from_timestamp,
            $to_timestamp,
        ] = $this->dateToRange($date);

        return $this->produceResult(
            [
                'active_conversations' => $this->getActiveConversations($from_timestamp, $to_timestamp),
                'active_conversations_by_type' => $this->getActiveConversationsByType($from_timestamp, $to_timestamp),
                'messages_count' => $this->getMessagesCount($from_timestamp, $to_timestamp),
                'messages_count_by_conversation_type' => $this->getMessagesCountByConversationType($from_timestamp, $to_timestamp),
                'user_ids_who_sent_message' => $this->getUserIdsWhoSentMessage($from_timestamp, $to_timestamp),
            ],
            $date
        );
    }

    private function getActiveConversations(string $from_timestamp, string $to_timestamp): int
    {
        return (int) $this->connection->executeFirstCell(
        'SELECT COUNT(c.id) AS conversation_count
             FROM conversations c
             WHERE EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.id AND m.created_on BETWEEN ? AND ?)',
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );
    }

    private function getActiveConversationsByType(string $from_timestamp, string $to_timestamp): array
    {
        $result = [];

        $rows = $this->connection->execute(
        'SELECT c.type, COUNT(c.id) AS conversation_count
             FROM conversations c
             WHERE EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.id AND m.created_on BETWEEN ? AND ?)
             GROUP BY c.type',
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );

        if ($rows) {
            foreach ($rows as $row) {
                $result[$this->prepareConversationType($row['type'])] = $row['conversation_count'];
            }
        }

        return $result;
    }

    private function getMessagesCount(string $from_timestamp, string $to_timestamp): int
    {
        return (int) $this->connection->executeFirstCell(
            'SELECT COUNT(id) AS messages_count
                 FROM messages
                 WHERE created_on BETWEEN ? AND ?',
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );
    }

    private function getMessagesCountByConversationType(string $from_timestamp, string $to_timestamp): array
    {
        $result = [];

        $rows = $this->connection->execute(
            'SELECT c.type, COUNT(m.id) AS messages_count
             FROM messages m
             LEFT JOIN conversations c ON c.id = m.conversation_id
             WHERE m.created_on BETWEEN ? AND ?
             GROUP BY c.type',
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );

        if ($rows) {
            foreach ($rows as $row) {
                $result[$this->prepareConversationType($row['type'])] = $row['messages_count'];
            }
        }

        return $result;
    }

    private function prepareConversationType(string $type): string
    {
        return str_replace('ActiveCollab\\Module\\System\\Model\\Conversation\\', '', $type);
    }

    private function getUserIdsWhoSentMessage(string $from_timestamp, string $to_timestamp): array
    {
        return (array) $this->connection->executeFirstColumn(
            'SELECT DISTINCT created_by_id
            FROM messages
            WHERE created_on BETWEEN ? AND ?',
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );
    }
}
