<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use Client;
use DateValue;
use DBConnection;
use User;

class DefaultStartPageCollection extends Collection
{
    private DBConnection $connection;

    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getValueFor(DateValue $date): ResultInterface
    {
        $total = 0;
        $pages = [];

        $user_ids = $this->connection->executeFirstColumn(
            'SELECT `id` FROM `users` WHERE `type` != ? AND `is_archived` = ? AND `is_trashed` = ?',
            [
                Client::class,
                false,
                false,
            ]
        );

        $values = $this->connection->executeFirstColumn(
            'SELECT `value` FROM `config_option_values` WHERE `name` = ? AND `parent_type` = ? AND `parent_id` IN (?)',
            [
                'homepage',
                User::class,
                $user_ids ?? [0],
            ]
        );

        if ($values) {
            $total = count($values);

            foreach ($values as $value) {
                $page = unserialize($value);

                if (!array_key_exists($page, $pages)) {
                    $pages[$page] = 1;
                } else {
                    ++$pages[$page];
                }
            }
        }

        return $this->produceResult(
            [
                'total' => $total,
                'by_page' => $pages,
            ],
            $date
        );
    }
}
