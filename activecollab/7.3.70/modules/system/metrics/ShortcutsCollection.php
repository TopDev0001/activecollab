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

class ShortcutsCollection extends Collection
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

        $number_of_internal = $this->getSumOfShortcuts($from_timestamp, $to_timestamp, true);
        $number_of_external = $this->getSumOfShortcuts($from_timestamp, $to_timestamp, false);

        return $this->produceResult(
            [
                'number_of_internal_links' => $number_of_internal,
                'number_of_external_links' => $number_of_external,
                'total' => $number_of_external + $number_of_internal,
            ],
            $date
        );
    }

    private function getSumOfShortcuts(string $from_timestamp, string $to_timestamp, bool $have_relative): int
    {
        $not = !$have_relative ? '' : ' NOT';

        return (int) $this->connection->executeFirstCell(
            "SELECT COUNT(id) AS number 
                 FROM `shortcuts` 
                 WHERE `relative_url` IS{$not} NULL AND `created_on` BETWEEN ? AND ?",
            [
                $from_timestamp,
                $to_timestamp,
            ]
        );
    }
}
