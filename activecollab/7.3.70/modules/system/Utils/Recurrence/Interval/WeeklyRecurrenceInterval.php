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

class WeeklyRecurrenceInterval extends RecurrenceInterval implements WeeklyRecurrenceIntervalInterface
{
    private $recur_on_weekdays;
    private $week_number_modifier;

    public function __construct(array $recur_on_weekdays, int $week_number_modifier = self::EVERY_WEEK)
    {
        if (count($recur_on_weekdays) > 7) {
            throw new InvalidArgumentException('Valid list of workdays expected.');
        }

        foreach ($recur_on_weekdays as $workday) {
            if (!is_int($workday) || $workday < 0 || $workday > 6) {
                throw new InvalidArgumentException('Valid list of workdays expected.');
            }
        }

        if ($week_number_modifier < self::EVERY_WEEK || $week_number_modifier > self::ON_EVEN_WEEKS) {
            throw new InvalidArgumentException('Valid week number modifier expected (0, 1 or 2).');
        }

        $this->recur_on_weekdays = $recur_on_weekdays;
        $this->week_number_modifier = $week_number_modifier;
    }

    public function shouldRecurOnDay(DateValue $day): bool
    {
        if (in_array($day->getWeekday(), $this->recur_on_weekdays)) {
            switch ($this->week_number_modifier) {
                case self::ON_ODD_WEEKS:
                    return $day->getWeek() % 2 === 1;
                case self::ON_EVEN_WEEKS:
                    return $day->getWeek() % 2 === 0;
                default:
                    return true;
            }
        }

        return false;
    }

    public function getNextRecurrence(DateValue $last_recurrence = null): ?DateValue
    {
        $reference = $this->getReferenceDate($last_recurrence);

        if (empty($last_recurrence) && $this->shouldRecurOnDay($reference)) {
            return $reference;
        }

        $next_weekday_name = $this->getWeekdayName(
            $this->getNextWeekday($this->recur_on_weekdays, $reference->getWeekday())
        );

        $next_weekday = new DateValue(
            strtotime(
                sprintf(
                    'next %s',
                    $next_weekday_name
                ),
                $reference->getTimestamp()
            )
        );

        if ($this->week_number_modifier != self::EVERY_WEEK && !$this->shouldRecurOnDay($next_weekday)) {
            return new DateValue(strtotime('+1 week', $next_weekday->getTimestamp()));
        } else {
            return $next_weekday;
        }
    }

    private function getNextWeekday(array $recur_on_weekdays, int $weekday): int
    {
        foreach ($recur_on_weekdays as $recur_on_weekday) {
            if ($recur_on_weekday > $weekday) {
                return $recur_on_weekday;
            }
        }

        return $recur_on_weekdays[0] ?? 1;
    }
}
