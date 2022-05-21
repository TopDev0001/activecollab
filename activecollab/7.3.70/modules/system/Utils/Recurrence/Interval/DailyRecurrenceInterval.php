<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval;

use DateValue;

class DailyRecurrenceInterval extends RecurrenceInterval
{
    public function getNextRecurrence(DateValue $last_recurrence = null): ?DateValue
    {
        $reference = $this->getReferenceDate($last_recurrence);

        if (empty($last_recurrence) && $this->shouldRecurOnDay($reference)) {
            return $reference;
        }

        return new DateValue(strtotime('+1 day', $reference->getTimestamp()));
    }

    public function shouldRecurOnDay(DateValue $day): bool
    {
        return true;
    }
}
