<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval;

use DateValue;

class NeverRecurrenceInterval extends RecurrenceInterval
{
    public function getNextRecurrence(DateValue $last_recurrence = null): ?DateValue
    {
        return null;
    }

    public function shouldRecurOnDay(DateValue $day): bool
    {
        return false;
    }
}
