<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\DatesRescheduleCalculator;

use Angie\Globalization;
use DateValue;

class DatesRescheduleCalculator implements DatesRescheduleCalculatorInterface
{
    public function getDuration(DateValue $start_date, DateValue $end_date): int
    {
        $days_between = $start_date->daysBetween($end_date);

        if ($start_date->isWeekend() || $start_date->isDayOff() || $end_date->isWeekend() || $end_date->isDayOff()) {
            return $days_between;
        }

        return $days_between - count(
                Globalization::getNonWorkingDaysBetweenDates(
                    clone $start_date,
                    clone $end_date
                )
            );
    }

    public function getWorkingDays(DateValue $start_date, DateValue $end_date): int
    {
        $working_days = count(
            Globalization::getWorkingDaysBetweenDates(
                clone $start_date,
                clone $end_date
            )
        );

        if ($this->startAndEndDateAreWorkingDays($start_date, $end_date)) {
            return $working_days - 2;
        } elseif ($this->startOrEndDateIsNonWorkingDay($start_date, $end_date)) {
            return $working_days - 1;
        } else {
            return 0;
        }
    }

    private function startAndEndDateAreWorkingDays(DateValue $start_date, DateValue $end_date): bool
    {
        return !$start_date->isWeekend() && !$start_date->isDayOff() &&
            !$end_date->isWeekend() && !$end_date->isDayOff();
    }

    private function startOrEndDateIsNonWorkingDay(DateValue $start_date, DateValue $end_date): bool
    {
        return (($start_date->isWeekend() || $start_date->isDayOff()) && !$end_date->isWeekend() && !$end_date->isDayOff()) ||
            (($end_date->isWeekend() || $end_date->isDayOff()) && !$start_date->isWeekend() && !$start_date->isDayOff());
    }

    public function addDays(DateValue $start_date, int $days): DateValue
    {
        if ($days === 0) {
            return $start_date;
        } else {
            $day_to_add = $days > 0 ? 1 : -1;
            $end_date = clone $start_date;

            while ($days !== 0) {
                $end_date->addDays($day_to_add);

                if (!$end_date->isWeekend() && !$end_date->isDayOff()) {
                    $days = $days - $day_to_add;
                }
            }

            return $end_date;
        }
    }
}
