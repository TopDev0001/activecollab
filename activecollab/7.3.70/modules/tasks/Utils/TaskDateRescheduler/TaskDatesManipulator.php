<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler;

use ActiveCollab\Module\Tasks\Utils\DatesRescheduleCalculator\DatesRescheduleCalculatorInterface;
use DateValue;
use Task;

class TaskDatesManipulator implements TaskDatesCorrectorInterface, TaskDatesInjectorInterface
{
    private $dates_reschedule_calculator;

    public function __construct(
        DatesRescheduleCalculatorInterface $dates_reschedule_calculator
    )
    {
        $this->dates_reschedule_calculator = $dates_reschedule_calculator;
    }

    public function correctDates(Task $task, DateValue &$start_on, DateValue &$due_on): void
    {
        $old_start_on = $task->getStartOn();
        $old_due_on = $task->getDueOn();

        if (!($old_start_on instanceof DateValue)) {
            $old_start_on = clone $start_on;
        }

        if (!($old_due_on instanceof DateValue)) {
            $old_due_on = clone $due_on;
        }

        $old_diff_raw = $old_start_on->daysBetween($old_due_on);
        $new_diff_raw = $start_on->daysBetween($due_on);

        if ($old_diff_raw === $new_diff_raw) {
            $old_diff = $this->dates_reschedule_calculator->getDuration($old_start_on, $old_due_on);

            $due_on = $start_on->addDays($old_diff < 0 ? 0 : $old_diff, false);

            while ($start_on->isWeekend() || $start_on->isDayOff()) {
                $start_on->addDays(1);
                $due_on->addDays(1);
            }

            if (!$start_on->isSameDay($due_on)) {
                while ($due_on->isWeekend() || $due_on->isDayOff()) {
                    $due_on->addDays(1);
                }
            }

            $new_diff = $this->dates_reschedule_calculator->getDuration($start_on, $due_on);

            if ($old_diff > $new_diff) {
                do {
                    $due_on->addDays(1);

                    if (!$due_on->isWeekend() && !$due_on->isDayOff()) {
                        $new_diff++;
                    }
                } while ($old_diff > $new_diff);
            }
        } elseif (
            ($start_on->isWeekend() || $start_on->isDayOff()) &&
            ($due_on->isWeekend() || $due_on->isDayOff())
        ) {
            while ($start_on->isWeekend() || $start_on->isDayOff()) {
                $start_on->addDays(1);
                $due_on->addDays(1);
            }

            while ($due_on->isWeekend() || $due_on->isDayOff()) {
                $due_on->addDays(1);
            }
        } else {
            while ($start_on->isWeekend() || $start_on->isDayOff()) {
                $start_on->addDays(1);
            }
            while ($due_on->isWeekend() || $due_on->isDayOff()) {
                $due_on->addDays(1);
            }
        }
    }

    public function injectDates(DateValue $from, DateValue $to, array &$tasks): void
    {
        $days = 0;

        if ($from->getTimestamp() < $to->getTimestamp()) {
            $days = $this->dates_reschedule_calculator->getWorkingDays($from, $to);
        }

        $total = count($tasks);
        $each = floor($days / $total);
        $last = $each;

        if ($days > $total && $days % $total != 0) {
            $last = $days - ($each * ($total - 1));
        }

        if ($each > 0) {
            --$each;
        }

        if ($last > 0) {
            --$last;
        }

        $date = $from->addDays(1, false);

        // @TODO: (PHP 7 >= 7.3.0) When requirements has been met, switch to - array_key_last
        $index = 0;
        $last_index = $total - 1;

        foreach ($tasks as &$task) {
            if ($index == $last_index) {
                $each = $last;
            }

            $start_on = clone $date;

            while ($start_on->isWeekend() || $start_on->isDayOff()) {
                $start_on->addDays(1);
            }

            $count = 0;
            $due_on = clone $start_on;

            while ($each > $count) {
                $due_on->addDays(1);

                if (!$due_on->isWeekend() && !$due_on->isDayOff()) {
                    $count++;
                }
            }

            $task->setStartOn($start_on->format('Y-m-d'));
            $task->setDueOn($due_on->format('Y-m-d'));

            $date = $due_on->addDays(1, false);

            $index++;
        }
    }
}
