<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\DatesRescheduleCalculator;

use DateValue;

interface DatesRescheduleCalculatorInterface
{
    public function getDuration(DateValue $start_date, DateValue $end_date): int;

    public function getWorkingDays(DateValue $start_date, DateValue $end_date): int;

    public function addDays(DateValue $start_date, int $days): DateValue;
}
