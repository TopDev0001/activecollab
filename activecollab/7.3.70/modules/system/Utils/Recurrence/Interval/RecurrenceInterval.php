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

abstract class RecurrenceInterval implements RecurrenceIntervalInterface
{
    public function getRecurrencesInRange(DateValue $from, DateValue $to): ?iterable
    {
        $result = [];

        DateValue::iterateDaily(
            $from,
            $to,
            function (DateValue $current_date) use (&$result) {
                if ($this->shouldRecurOnDay($current_date)) {
                    $result[] = $current_date;
                }
            }
        );

        return $result;
    }

    protected function isValidMonth(int $month): bool
    {
        return $month >= 1 && $month <= 12;
    }

    protected function isValidQuarterMonth(int $month): bool
    {
        return $month >= 1 && $month <= 3;
    }

    protected function isValidHalfYearMonth(int $month): bool
    {
        return $month >= 1 && $month <= 6;
    }

    protected function isValidMonthDay(int $month_day): bool
    {
        return $month_day >= 1 && $month_day <= self::LAST_MONTH_DAY;
    }

    protected function getWeekdayName(int $weekday): string
    {
        switch ($weekday) {
            case 0:
                return 'Sunday';
            case 1:
                return 'Monday';
            case 2:
                return 'Tuesday';
            case 3:
                return 'Wednesday';
            case 4:
                return 'Thursday';
            case 5:
                return 'Friday';
            case 6:
                return 'Saturday';
            default:
                throw new InvalidArgumentException('Valid workday number expected.');
        }
    }

    private $last_month_days = [];

    protected function getLastDayInMonth(int $year, int $month): int
    {
        if (empty($this->last_month_days[$year][$month])) {
            if (empty($this->last_month_days[$year])) {
                $this->last_month_days[$year] = [];
            }

            $this->last_month_days[$year][$month] = DateValue::endOfMonth($month, $year)->getDay();
        }

        return $this->last_month_days[$year][$month];
    }

    protected function getReferenceDate(DateValue $last_recurrence = null): DateValue
    {
        return $last_recurrence ?? new DateValue();
    }
}
