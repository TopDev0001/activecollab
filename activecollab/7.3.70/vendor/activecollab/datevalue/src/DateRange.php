<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue;

class DateRange implements DateRangeInterface
{
    use DateRangeTrait;

    public function __construct(DateValueInterface $start_date, DateValueInterface $end_date)
    {
        $this->setStartAndEndDate($start_date, $end_date);
    }
}
