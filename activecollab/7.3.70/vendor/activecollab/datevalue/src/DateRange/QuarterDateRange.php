<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue\DateRange;

use ActiveCollab\DateValue\DateRange;

class QuarterDateRange extends DateRange implements QuarterDateRangeInterface
{
    private $year;
    private $quarter;

    public function __construct(int $year, int $quarter)
    {
        parent::__construct(...(new DateRangeResolver())->getQuarterDateRange($year, $quarter));

        $this->year = $year;
        $this->quarter = $quarter;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getQuarter(): int
    {
        return $this->quarter;
    }

    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'year' => $this->year,
                'quarter' => $this->quarter,
            ]
        );
    }
}
