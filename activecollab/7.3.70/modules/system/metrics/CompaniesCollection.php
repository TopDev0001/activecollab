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

class CompaniesCollection extends Collection
{
    private DBConnection $connection;
    private int $owner_company_id;

    public function __construct(DBConnection $connection, int $owner_company_id)
    {
        $this->connection = $connection;
        $this->owner_company_id = $owner_company_id;
    }

    public function getValueFor(DateValue $date): ResultInterface
    {
        [
            $total,
            $total_active,
            $total_archived,
        ] = $this->countCompaniesOnDay($date);

        return $this->produceResult(
            [
                'owner_company_name' => $this->getOwnerCompanyNameOnDay($date),
                'total' => $total,
                'total_active' => $total_active,
                'total_archived' => $total_archived,
            ],
            $date
        );
    }

    private function getOwnerCompanyNameOnDay(DateValue $date): string
    {
        $company_name = $this->connection->executeFirstCell(
            'SELECT `name` FROM `companies` WHERE `id` = ?',
            [
                $this->owner_company_id,
            ]
        );

        if (empty($company_name)) {
            $company_name = 'Owner Company';
        }

        return $company_name;
    }

    private function countCompaniesOnDay(DateValue $date): array
    {
        $total = 0;
        $total_active = 0;
        $total_archived = 0;

        $until_timestamp = $this->dateToRange($date)[1];

        $rows = $this->connection->execute(
            'SELECT
                COUNT(`id`) AS "row_count",
                (`archived_on` IS NOT NULL AND `archived_on` <= ?) AS "is_archived"
                FROM `companies`
                WHERE (`trashed_on` IS NULL OR `trashed_on` > ?) AND `created_on` <= ?
                GROUP BY `archived_on`',
            [
                $until_timestamp,
                $until_timestamp,
                $until_timestamp,
            ]
        );

        if ($rows) {
            foreach ($rows as $row) {
                $total += $row['row_count'];

                if ($row['is_archived']) {
                    $total_archived += $row['row_count'];
                } else {
                    $total_active += $row['row_count'];
                }
            }
        }

        return [
            $total,
            $total_active,
            $total_archived,
        ];
    }
}
