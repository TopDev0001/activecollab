<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval;

use Angie\Globalization\WorkdayResolverInterface;
use DateValue;

class WorkdayRecurrenceInterval extends RecurrenceInterval
{
    private $workday_resolver;

    public function __construct(WorkdayResolverInterface $workday_resolver)
    {
        $this->workday_resolver = $workday_resolver;
    }

    public function getNextRecurrence(DateValue $last_recurrence = null): ?DateValue
    {
        $reference = $this->getReferenceDate($last_recurrence);

        if (empty($last_recurrence) && $this->shouldRecurOnDay($reference)) {
            return $reference;
        }

        return new DateValue(
            strtotime(
                sprintf(
                    'next %s',
                    $this->getWeekdayName(
                        $this->getNextWorkday($this->workday_resolver->getWorkdays(), $reference->getWeekday())
                    )
                ),
                $reference->getTimestamp()
            )
        );
    }

    private function getNextWorkday(array $workdays, int $weekday): int
    {
        foreach ($workdays as $workday) {
            if ($workday > $weekday) {
                return $workday;
            }
        }

        return $workdays[0] ?? 1;
    }

    public function shouldRecurOnDay(DateValue $day): bool
    {
        return $this->workday_resolver->isWorkday($day);
    }
}
