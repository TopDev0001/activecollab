<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval\Factory;

use ActiveCollab\Module\System\Utils\Recurrence\Interval\RecurrenceIntervalInterface;

interface RecurrenceIntervalFactoryInterface
{
    public function never(): RecurrenceIntervalInterface;
    public function daily(bool $workdays_only = false): RecurrenceIntervalInterface;
    public function weekly(array $recur_on_weekdays, int $week_number_modifier): RecurrenceIntervalInterface;
    public function monthly(int $recur_on_month_day): RecurrenceIntervalInterface;
    public function quarterly(int $recur_on_quarter_month, int $recur_on_month_day): RecurrenceIntervalInterface;
    public function semiYearly(int $recur_on_halfyear_month, int $recur_on_month_day): RecurrenceIntervalInterface;
    public function yearly(int $recur_on_month, int $recur_on_month_day): RecurrenceIntervalInterface;
}
