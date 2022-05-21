<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue\DateRange;

use ActiveCollab\DateValue\DateRange;

class MonthDateRange extends DateRange implements MonthDateRangeInterface
{
    private $year;
    private $month;

    public function __construct(int $year, int $month)
    {
        parent::__construct(...(new DateRangeResolver())->getMonthDateRange($year, $month));

        $this->year = $year;
        $this->month = $month;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'year' => $this->year,
                'month' => $this->month,
            ]
        );
    }
}
