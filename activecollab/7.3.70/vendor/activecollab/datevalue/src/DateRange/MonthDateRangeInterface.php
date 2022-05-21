<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue\DateRange;

use ActiveCollab\DateValue\DateRangeInterface;

interface MonthDateRangeInterface extends DateRangeInterface
{
    public function getYear(): int;
    public function getMonth(): int;
}
