<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\GhostTasks\Resolver;

use ActiveCollab\Module\Tasks\Utils\GhostTasks\GhostTask;
use DateValue;
use RecurringTask;
use RecurringTasks;

class GhostTasksResolver implements GhostTasksResolverInterface
{
    public function getForCalendar(array $ids, DateValue $from_date, DateValue $to_date): array
    {
        $ids = !empty($ids) ? $ids : [0];
        $today = DateValue::now()->beginningOfDay();

        // Check if range in current month, and if is take $from_date to $today day
        if ($today->getTimestamp() > $from_date->getTimestamp()) {
            $from_date = $today;
        }

        /** @var RecurringTask[] $recurring_tasks */
        $recurring_tasks = RecurringTasks::findBySQL(
            'SELECT * FROM `recurring_tasks` WHERE `id` IN (?)',
            $ids
        );

        $result = [];

        if (!empty($recurring_tasks)) {
            foreach ($recurring_tasks as $recurring_task) {
                $start_in_value = $recurring_task->getStartIn();
                $due_in_value = $recurring_task->getDueIn();

                $last_trigger_on = !empty($recurring_task->getLastTriggerOn()) ? $recurring_task->getLastTriggerOn()->beginningOfDay()->getTimestamp() : null;
                $created_on = !empty($recurring_task->getCreatedOn()) ? $recurring_task->getCreatedOn()->beginningOfDay()->getTimestamp() : null;

                $repeat_amount = $recurring_task->getRepeatAmount();

                if ($recurring_task->getRepeatFrequency() == RecurringTask::REPEAT_FREQUENCY_MONTHLY) {
                    $next_trigger_on = DateValue::makeFromTimestamp(
                        strtotime(
                            sprintf('-%s day', $due_in_value),
                            $from_date->getTimestamp()
                        )
                    );
                } elseif ($recurring_task->getRepeatFrequency() == RecurringTask::REPEAT_FREQUENCY_DAILY && $repeat_amount == 0) {
                    $range = $recurring_task->getStartDueOnRangeSkipWeekend($from_date->getTimestamp(), true);
                    $next_trigger_on = DateValue::makeFromTimestamp(
                        strtotime(
                            sprintf('-%s day', $range['due_in']),
                            $from_date->getTimestamp()
                        )
                    );
                } else {
                    $next_trigger_on = DateValue::makeFromTimestamp(
                        strtotime(
                            sprintf('-%s day', $due_in_value),
                            $from_date->getTimestamp()
                        )
                    );
                }

                do {
                    $timestamp = $next_trigger_on->getTimestamp();
                    switch ($recurring_task->getRepeatFrequency()) {
                        case RecurringTask::REPEAT_FREQUENCY_DAILY:
                            $next_trigger_on = DateValue::makeFromTimestamp(strtotime('+1 day', $timestamp));

                            // Jump Weekend
                            if ($repeat_amount == 0) {
                                if ($next_trigger_on->isWeekend()) {
                                    $next_trigger_on = DateValue::makeFromTimestamp(strtotime('next monday', $timestamp));
                                }

                                $skipWeekend = $recurring_task->getStartDueOnRangeSkipWeekend($next_trigger_on->getTimestamp());
                                $start_in_value = $skipWeekend['start_in'];
                                $due_in_value = $skipWeekend['due_in'];
                            }
                            break;
                        case RecurringTask::REPEAT_FREQUENCY_WEEKLY:
                            $next_trigger_on = DateValue::makeFromTimestamp(strtotime('next ' . $this->getDayString($repeat_amount), $timestamp));
                            break;
                        case RecurringTask::REPEAT_FREQUENCY_MONTHLY:
                            $monthName = date('F', $timestamp);

                            // 29 represents last day in month
                            if ($repeat_amount == 29) {
                                $repeat_amount = 'last day of';
                            }

                            $timestamp_date = DateValue::makeFromTimestamp(strtotime($repeat_amount . ' ' . $monthName, $timestamp));

                            $next_trigger_on = DateValue::makeFromTimestamp(strtotime('this month', $timestamp_date->getTimestamp()));

                            // If current $timestamp same like $next_trigger_on then set next month
                            if ($timestamp == $next_trigger_on->getTimestamp()) {
                                if ($recurring_task->getRepeatAmount() == 29) {
                                    $next_trigger_on = DateValue::makeFromTimestamp(strtotime('last day of next month', $timestamp));
                                } else {
                                    $next_trigger_on = DateValue::makeFromTimestamp(strtotime('next month', $timestamp_date->getTimestamp()));
                                }
                            }

                            break;
                        default:
                            $next_trigger_on = DateValue::makeFromTimestamp(strtotime('+1 day', $to_date->getTimestamp()));
                    }

                    $start_in = DateValue::makeFromTimestamp(strtotime('+' . $start_in_value . ' day', $next_trigger_on->getTimestamp()));
                    $due_in = DateValue::makeFromTimestamp(strtotime('+' . $due_in_value . ' day', $next_trigger_on->getTimestamp()));

                    if ($start_in->getTimestamp() <= $to_date->getTimestamp()
                        && $due_in->getTimestamp() >= $from_date->getTimestamp()
                        && $created_on <= $next_trigger_on->getTimestamp()
                        && $last_trigger_on != $next_trigger_on->getTimestamp()
                    ) {
                        $result[] = new GhostTask(
                            $recurring_task,
                            count($result),
                            $start_in,
                            $due_in,
                            $next_trigger_on
                        );
                    }
                } while ($start_in->getTimestamp() <= $to_date->getTimestamp());
            }
        }

        return $result;
    }

    private function getDayString(int $day): string
    {
        switch ($day) {
            case 2:
                return 'tuesday';
            case 3:
                return 'wednesday';
            case 4:
                return 'thursday';
            case 5:
                return 'friday';
            case 6:
                return 'saturday';
            case 7:
                return 'sunday';
            default:
                return 'monday';
        }
    }
}
