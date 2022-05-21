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

class SemiYearlyRecurrenceInterval extends RecurrenceInterval
{
    private $recur_on_halfyear_month;
    private $recur_on_month_day;

    public function __construct(int $recur_on_halfyear_month, int $recur_on_month_day)
    {
        if (!$this->isValidHalfYearMonth($recur_on_halfyear_month)) {
            throw new InvalidArgumentException('Valid month is required.');
        }

        if (!$this->isValidMonthDay($recur_on_month_day)) {
            throw new InvalidArgumentException('Valid day of month is required.');
        }

        $this->recur_on_halfyear_month = $recur_on_halfyear_month;
        $this->recur_on_month_day = $recur_on_month_day;
    }

    public function shouldRecurOnDay(DateValue $day): bool
    {
        $halfyearly_months = [
            $this->recur_on_halfyear_month,
            $this->recur_on_halfyear_month + 6,
        ];

        if (!in_array($day->getMonth(), $halfyearly_months)) {
            return false;
        }

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

        $is_first_half_of_the_year = $this->isFirstHalfYear($reference);

        $reference_in_period = $this->getRecurrenceInHalfYearPeriod(
            $is_first_half_of_the_year ? 1 : 2,
            $reference->getYear(),
            $this->recur_on_halfyear_month,
            $this->recur_on_month_day
        );

        if ($reference->format('Y-m-d') < $reference_in_period->format('Y-m-d')) {
            return $reference_in_period;
        }

        if ($is_first_half_of_the_year) {
            $next_halfyear_period = 2;
            $next_year = $reference->getYear();
        } else {
            $next_halfyear_period = 1;
            $next_year = $reference->getYear() + 1;
        }

        return $this->getRecurrenceInHalfYearPeriod(
            $next_halfyear_period,
            $next_year,
            $this->recur_on_halfyear_month,
            $this->recur_on_month_day
        );
    }

    private function getRecurrenceInHalfYearPeriod(
        int $halfyear_period,
        int $year,
        int $recur_on_halfyear_month,
        int $recur_on_month_day
    ): DateValue
    {
        $quarter_month = $this->getHalfYearPeriodMonth($halfyear_period, $recur_on_halfyear_month);

        return DateValue::makeFromString(
            sprintf(
                '%d-%d-%d',
                $year,
                $quarter_month,
                $recur_on_month_day === self::LAST_MONTH_DAY
                    ? $this->getLastDayInMonth($year, $quarter_month)
                    : $recur_on_month_day
            )
        );
    }

    private function getHalfYearPeriodMonth(int $halfyear_period, int $halfyear_period_month): int
    {
        return ($halfyear_period - 1) * 6 + $halfyear_period_month;
    }

    private function isFirstHalfYear(DateValue $day): bool
    {
        return $day->getMonth() <= 6;
    }
}
