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
use IReactions;

class ReactionsCollection extends Collection
{
    private $connection;

    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getValueFor(DateValue $date): ResultInterface
    {
        $total_reactions = 0;
        $reactions_by_type = [];

        [
            $from_timestamp,
            $to_timestamp,
        ] = $this->dateToRange($date);

        $rows = $this->connection->execute(
            'SELECT COUNT(`id`) AS "row_count", `type` FROM `reactions` WHERE `created_on` BETWEEN ? AND ? GROUP BY `type`',
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );

        if ($rows) {
            foreach ($rows as $row) {
                if (empty($row['type'])) {
                    continue;
                }

                $reaction_type = $this->getReactionTypeByClass($row['type']);

                if (empty($reaction_type)) {
                    continue;
                }

                $total_reactions += $row['row_count'];

                if (empty($reactions_by_type[$reaction_type])) {
                    $reactions_by_type[$reaction_type] = 0;
                }

                $reactions_by_type[$reaction_type] += $row['row_count'];
            }
        }

        return $this->produceResult(
            [
                'total' => $total_reactions,
                'reacting_users' => $total_reactions
                    ? $this->countReactingUsers($from_timestamp, $to_timestamp)
                    : 0,
                'by_type' => $reactions_by_type,
            ],
            $date
        );
    }

    public function getReactionTypeByClass(string $reaction_class): ?string
    {
        $reaction_type = array_search($reaction_class, IReactions::REACTION_TYPES);

        if (empty($reaction_type)) {
            return null;
        }

        return $reaction_type;
    }

    public function countReactingUsers(string $from_timestamp, string $to_timestamp): int
    {
        return (int) $this->connection->executeFirstCell(
            'SELECT COUNT(DISTINCT `created_by_id`) AS "row_count" FROM `reactions` WHERE `created_on` BETWEEN ? AND ?',
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );
    }
}
