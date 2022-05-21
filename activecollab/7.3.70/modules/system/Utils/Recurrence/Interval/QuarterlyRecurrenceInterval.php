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

class QuarterlyRecurrenceInterval extends RecurrenceInterval
{
    private $recur_on_quarter_month;
    private $recur_on_month_day;

    public function __construct(int $recur_on_quarter_month, int $recur_on_month_day)
    {
        if (!$this->isValidQuarterMonth($recur_on_quarter_month)) {
            throw new InvalidArgumentException('Valid month is required.');
        }

        if (!$this->isValidMonthDay($recur_on_month_day)) {
            throw new InvalidArgumentException('Valid day of month is required.');
        }

        $this->recur_on_quarter_month = $recur_on_quarter_month;
        $this->recur_on_month_day = $recur_on_month_day;
    }

    public function shouldRecurOnDay(DateValue $day): bool
    {
        $quarterly_months = $this->getQuarterlyMonths();

        if (!in_array($day->getMonth(), $quarterly_months)) {
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

        $reference_in_quarter = $this->getRecurrenceInQuarter(
            $reference->getQuarter(),
            $reference->getYear(),
            $this->recur_on_quarter_month,
            $this->recur_on_month_day
        );

        if ($reference->format('Y-m-d') < $reference_in_quarter->format('Y-m-d')) {
            return $reference_in_quarter;
        }

        if ($reference->getQuarter() === 4) {
            $next_quarter = 1;
            $next_year = $reference->getYear() + 1;
        } else {
            $next_quarter = $reference->getQuarter() + 1;
            $next_year = $reference->getYear();
        }

        return $this->getRecurrenceInQuarter(
            $next_quarter,
            $next_year,
            $this->recur_on_quarter_month,
            $this->recur_on_month_day
        );
    }

    private function getRecurrenceInQuarter(
        int $quarter,
        int $year,
        int $recur_on_quarter_month,
        int $recur_on_month_day
    ): DateValue
    {
        $quarter_month = $this->getQuarterMonth($quarter, $recur_on_quarter_month);

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

    private function getQuarterlyMonths(): array
    {
        return [
            $this->recur_on_quarter_month,
            $this->recur_on_quarter_month + 3,
            $this->recur_on_quarter_month + 6,
            $this->recur_on_quarter_month + 9,
        ];
    }

    private function getQuarterMonth(int $quarter, int $quarter_month): int
    {
        return ($quarter - 1) * 3 + $quarter_month;
    }
}
