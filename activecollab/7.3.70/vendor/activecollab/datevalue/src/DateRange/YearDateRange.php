<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue\DateRange;

use ActiveCollab\DateValue\DateRange;

class YearDateRange extends DateRange implements YearDateRangeInterface
{
    private $year;

    public function __construct(int $year)
    {
        parent::__construct(...(new DateRangeResolver())->getYearDateRange($year));

        $this->year = $year;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'year' => $this->year,
            ]
        );
    }
}
