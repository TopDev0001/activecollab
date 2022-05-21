<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval\Factory;

use ActiveCollab\Module\System\Utils\Recurrence\Interval\DailyRecurrenceInterval;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\MonthlyRecurrenceInterval;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\NeverRecurrenceInterval;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\QuarterlyRecurrenceInterval;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\RecurrenceIntervalInterface;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\SemiYearlyRecurrenceInterval;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\WeeklyRecurrenceInterval;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\WorkdayRecurrenceInterval;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\YearlyRecurrenceInterval;
use Angie\Globalization\WorkdayResolverInterface;

class RecurrenceIntervalFactory implements RecurrenceIntervalFactoryInterface
{
    private $workday_resolver;

    public function __construct(WorkdayResolverInterface $workday_resolver)
    {
        $this->workday_resolver = $workday_resolver;
    }

    public function never(): RecurrenceIntervalInterface
    {
        return new NeverRecurrenceInterval();
    }

    public function daily(bool $workdays_only = false): RecurrenceIntervalInterface
    {
        return $workdays_only
            ? new WorkdayRecurrenceInterval($this->workday_resolver)
            : new DailyRecurrenceInterval();
    }

    public function weekly(array $recur_on_weekdays, int $week_number_modifier): RecurrenceIntervalInterface
    {
        return new WeeklyRecurrenceInterval($recur_on_weekdays, $week_number_modifier);
    }

    public function monthly(int $recur_on_month_day): RecurrenceIntervalInterface
    {
        return new MonthlyRecurrenceInterval($recur_on_month_day);
    }

    public function quarterly(int $recur_on_quarter_month, int $recur_on_month_day): RecurrenceIntervalInterface
    {
        return new QuarterlyRecurrenceInterval($recur_on_quarter_month, $recur_on_month_day);
    }

    public function semiYearly(int $recur_on_halfyear_month, int $recur_on_month_day): RecurrenceIntervalInterface
    {
        return new SemiYearlyRecurrenceInterval($recur_on_halfyear_month, $recur_on_month_day);
    }

    public function yearly(int $recur_on_month, int $recur_on_month_day): RecurrenceIntervalInterface
    {
        return new YearlyRecurrenceInterval($recur_on_month, $recur_on_month_day);
    }
}
