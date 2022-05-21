<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval;

use DateValue;

interface RecurrenceIntervalInterface
{
    const LAST_MONTH_DAY = 29;

    public function shouldRecurOnDay(DateValue $day): bool;
    public function getNextRecurrence(DateValue $last_recurrence = null): ?DateValue;

    /**
     * @return DateValue[]|iterable|null
     */
    public function getRecurrencesInRange(DateValue $from, DateValue $to): ?iterable;
}
