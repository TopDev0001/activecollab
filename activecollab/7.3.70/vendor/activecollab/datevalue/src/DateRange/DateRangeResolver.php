<?php

/*
 * This file is part of the Active Collab DateValue project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DateValue\DateRange;

use ActiveCollab\DateValue\DateValue;
use LogicException;

class DateRangeResolver implements DateRangeResolverInterface
{
    public function getMonthDateRange(int $year, int $month): array
    {
        if ($month < 1 || $month > 12) {
            throw new LogicException("Value '$month' is not a valid month.");
        }

        return [
            new DateValue("first day of {$year}-{$month}"),
            new DateValue("last day of {$year}-{$month}"),
        ];
    }

    public function getQuarterDateRange(int $year, int $quarter): array
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new LogicException("Value '$quarter' is not a valid quarter.");
        }

        $first_month = ($quarter - 1) * 3 + 1;
        $last_month = $first_month + 2;

        $first_month = $first_month < 10 ? '0' . $first_month : (string) $first_month;
        $last_month = $last_month < 10 ? '0' . $last_month : (string) $last_month;

        return [
            new DateValue("first day of {$year}-{$first_month}"),
            new DateValue("last day of {$year}-{$last_month}"),
        ];
    }

    public function getYearDateRange(int $year): array
    {
        return [
            new DateValue("{$year}-01-01"),
            new DateValue("{$year}-12-31"),
        ];
    }
}
