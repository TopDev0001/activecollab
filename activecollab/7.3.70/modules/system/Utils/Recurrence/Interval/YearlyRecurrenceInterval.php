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

class YearlyRecurrenceInterval extends RecurrenceInterval
{
    private $recur_on_month;
    private $recur_on_month_day;

    public function __construct(int $recur_on_month, int $recur_on_month_day)
    {
        if (!$this->isValidMonth($recur_on_month)) {
            throw new InvalidArgumentException('Valid month is required.');
        }

        if (!$this->isValidMonthDay($recur_on_month_day)) {
            throw new InvalidArgumentException('Valid day of month is required.');
        }

        $this->recur_on_month = $recur_on_month;
        $this->recur_on_month_day = $recur_on_month_day;
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
        ] = $this->getNextRecurrenceYearAndMonth($reference, $this->recur_on_month, $this->recur_on_month_day);

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

    public function shouldRecurOnDay(DateValue $day): bool
    {
        if ($day->getMonth() !== $this->recur_on_month) {
            return false;
        }

        $reference_day = $this->recur_on_month_day === self::LAST_MONTH_DAY
            ? $this->getLastDayInMonth($day->getYear(), $day->getMonth())
            : $this->recur_on_month_day;

        return $day->getDay() === $reference_day;
    }

    private function getNextRecurrenceYearAndMonth(
        DateValue $reference,
        int $recur_on_month,
        int $recur_on_month_day
    ): array
    {
        if ($this->shouldRecurInNextYear($reference, $recur_on_month, $recur_on_month_day)) {
            return [
                $reference->getYear() + 1,
                $recur_on_month,
            ];
        } else {
            return [
                $reference->getYear(),
                $recur_on_month,
            ];
        }
    }

    private function shouldRecurInNextYear(DateValue $reference, int $recur_on_month, int $recur_on_month_day): bool
    {
        if ($recur_on_month < $reference->getMonth()) {
            return true;
        }

        if ($recur_on_month === $reference->getMonth()) {
            $check_agains_day = $recur_on_month_day === self::LAST_MONTH_DAY
                ? $this->getLastDayInMonth($reference->getYear(), $reference->getMonth())
                : $recur_on_month_day;

            return $check_agains_day <= $reference->getDay();
        }

        return false;
    }
}
