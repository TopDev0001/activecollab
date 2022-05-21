<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval;

use DateValue;
use InvalidArgumentException;

class MonthlyRecurrenceInterval extends RecurrenceInterval
{
    private $recur_on_month_day;

    public function __construct(int $recur_on_month_day)
    {
        if (!$this->isValidMonthDay($recur_on_month_day)) {
            throw new InvalidArgumentException('Valid day of month is required.');
        }

        $this->recur_on_month_day = $recur_on_month_day;
    }

    public function shouldRecurOnDay(DateValue $day): bool
    {
        $reference_day = $this->recur_on_month_day === self::LAST_MONTH_DAY
            ? $this->getLastDayInMonth($day->getYear(), $day->getMonth())
            : $this->recur_on_month_day;

        return $day->getDay() === $reference_day;
    }

    public function getNextRecurrence(DateValue $last_recurrence = null): ?DateValue
    {
        $reference = $this->getReferenceDate($last_recurrence);

        if (empty($last_recurrence) && $this->shouldRecurOnDay($reference)) {
            return $reference;
        }

        [
            $next_recurrence_year,
            $next_recurrence_month,
        ] = $this->getNextRecurrenceYearAndMonth($reference, $this->recur_on_month_day);

        $next_recurrence_day = $this->recur_on_month_day === self::LAST_MONTH_DAY
            ? $this->getLastDayInMonth($next_recurrence_year, $next_recurrence_month)
            : $this->recur_on_month_day;

        return DateValue::makeFromString(
            sprintf(
                '%d-%d-%d',
                $next_recurrence_year,
                $next_recurrence_month,
                $next_recurrence_day
            )
        );
    }

    private function getNextRecurrenceYearAndMonth(
        DateValue $reference,
        int $recur_on_month_day
    ): array
    {
        if ($reference->getDay() > self::LAST_MONTH_DAY || $reference->getDay() < $recur_on_month_day) {
            $next_recurrence_year = $reference->getYear();
            $next_recurrence_month = $reference->getMonth();
        } else {
            if ($reference->getMonth() == 12) {
                $next_recurrence_year = $reference->getYear() + 1;
                $next_recurrence_month = 1;
            } else {
                $next_recurrence_year = $reference->getYear();
                $next_recurrence_month = $reference->getMonth() + 1;
            }
        }

        return [
            $next_recurrence_year,
            $next_recurrence_month,
        ];
    }
}
