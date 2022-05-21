<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Framework level calendar events managear implementation.
 *
 * @package angie.frameworks.calendars
 * @subpackage models
 */
abstract class FwCalendarEvents extends BaseCalendarEvents
{
    /**
     * Returns true if $user can create a new events.
     *
     * @return bool
     */
    public static function canAdd(User $user, Calendar $calendar)
    {
        return $calendar->isCreatedBy($user) || $calendar->canView($user);
    }

    /**
     * Take $date_or_range input and return array with start and end dates (can be the same date).
     *
     * This function accepts:
     *
     * 1. DateValue instance
     * 2. Array of two elements, where each element is either string, integer or DateValue instance
     * 3. String representation of a date or timestamp
     *
     * @param  mixed             $date_or_range
     * @return DateValue[]
     * @throws InvalidParamError
     */
    public static function dateOrRangeToRange($date_or_range)
    {
        if ($date_or_range instanceof DateValue) {
            $from_date = $date_or_range;
        } elseif (is_array($date_or_range) && count($date_or_range) == 2) {
            [$from_date, $to_date] = $date_or_range;

            if (!($from_date instanceof DateValue)) {
                if ($from_date && (is_string($from_date) || is_int($from_date))) {
                    $from_date = new DateValue($from_date);
                } else {
                    throw new InvalidParamError('date_or_range', $date_or_range, 'First element of range needs to be an instance or DateValue, time stamp or string representation of a date');
                }
            }

            if (!($to_date instanceof DateValue)) {
                if ($to_date && (is_string($to_date) || is_int($to_date))) {
                    $to_date = new DateValue($to_date);
                } else {
                    throw new InvalidParamError('date_or_range', $date_or_range, 'Second element of range needs to be an instance or DateValue, time stamp or string representation of a date');
                }
            }
        } elseif ($date_or_range && (is_string($date_or_range) || is_int($date_or_range))) {
            $from_date = new DateValue($date_or_range);
        } else {
            throw new InvalidParamError('date_or_range', $date_or_range, '$date_or_range is expected to be an instance of DateValue class, array with start and end dates, timestamp or a strict representation of a date');
        }

        if (!isset($to_date)) {
            $to_date = $from_date;
        }

        return [$from_date, $to_date];
    }

    /**
     * Prepare conditions based on date or range.
     *
     * @param  mixed  $date_or_range
     * @return string
     */
    public static function prepareConditionsBasedOnDateOrRange($date_or_range)
    {
        /** @var DateValue $range_start */
        /** @var DateValue $range_end */
        [$range_start, $range_end] = CalendarEvents::dateOrRangeToRange($date_or_range);

        if ($range_start->isSameDay($range_end)) {
            return CalendarEvents::prepareConditionsForDay($range_start);
        } else {
            return CalendarEvents::prepareConditionsForRange($range_start, $range_end);
        }
    }

    /**
     * Prepare conditions for a given day.
     *
     * @return string
     */
    public static function prepareConditionsForDay(DateValue $for)
    {
        $escaped_day = DB::escape($for);

        // Exact events defined for a given day
        $exact_match = "calendar_events.starts_on <= $escaped_day AND calendar_events.ends_on >= $escaped_day";

        $day = $for->getDay();
        $month = $for->getMonth();
        $week_day = $for->getWeekday() + 1; // Add 1 to meet MySQL's DAYOFWEEK() result (ODBC complient)

        $recurring_match =
            "(calendar_events.repeat_event = 'daily') OR " . // All daily events
            "(calendar_events.repeat_event = 'weekly' AND DAYOFWEEK(calendar_events.starts_on) = '$week_day') OR " . // All weekly events that fall on a given day
            "(calendar_events.repeat_event = 'monthly' AND DAY(calendar_events.starts_on) = '$day') OR " . // All monthly events that fall on a given month day
            "(calendar_events.repeat_event = 'yearly' AND DAY(calendar_events.starts_on) = '$day' AND MONTH(calendar_events.starts_on) = '$month')"; // All yearly events that fall on a given day of a given month

        $ignore_past_repeting_events = "(calendar_events.repeat_event != 'dont' AND calendar_events.ends_on < $escaped_day)";

        return "($exact_match OR ($ignore_past_repeting_events AND ($recurring_match)))";
    }

    /**
     * Prepare conditions for a given date range.
     *
     * @return string
     */
    public static function prepareConditionsForRange(DateValue $from, DateValue $to)
    {
        $escaped_from_day = DB::escape($from);
        $escaped_to_day = DB::escape($to);

        // Find all exact event definitions
        $exact_match = "(((calendar_events.starts_on >= $escaped_from_day AND calendar_events.starts_on <= $escaped_to_day)) OR ((calendar_events.ends_on >= $escaped_from_day AND calendar_events.ends_on <= $escaped_to_day)))"; // All events that either start or end in the given range

        // Ignore repeating events that start after the range
        $ignore_past_repeting_events = DB::prepare("(calendar_events.repeat_event != 'dont' AND calendar_events.starts_on < ?)", $to);

        if (CalendarEvents::matchWholeYear($from, $to)) {
            return "($exact_match OR ($ignore_past_repeting_events AND calendar_events.repeat_event != 'dont'))"; // Exact match or any repeating event
        } else {
            $repeat_event_conditions = [
                "(calendar_events.repeat_event = 'daily')", // Any daily event matches the range filter
            ];

            // Prepare weekdays match
            $weekdays = CalendarEvents::matchWeekdays($from, $to);

            if ($weekdays == 'any') {
                $repeat_event_conditions[] = "(calendar_events.repeat_event = 'weekly')";
            } else {
                $repeat_event_conditions[] = DB::prepare(
                    "(calendar_events.repeat_event = 'weekly' AND WEEKDAY(calendar_events.starts_on) IN (?))",
                    [CalendarEvents::phpWeekdaysToMySQLWeekdays($weekdays)]
                );
            }

            foreach (CalendarEvents::matchYearMonthAndDay($from, $to) as $year => $months) {
                $full_months = [];
                $all_days = [];

                foreach ($months as $month => $days) {
                    if ($days === 'any') {
                        $full_months[] = $month;
                    } else {
                        $repeat_event_conditions[] = DB::prepare("(calendar_events.repeat_event = 'yearly' AND MONTH(calendar_events.starts_on) = '$month' AND DAY(calendar_events.starts_on) IN (?))", $days);

                        $all_days = array_merge($all_days, $days);
                    }
                }

                if ($full_months) {
                    $repeat_event_conditions[] = "(calendar_events.repeat_event = 'monthly')";
                    $repeat_event_conditions[] = DB::prepare("(calendar_events.repeat_event = 'yearly' AND MONTH(calendar_events.starts_on) IN (?))", $full_months);
                } else {
                    array_unique($all_days);
                    sort($all_days);

                    $all_days_count = count($all_days);

                    if ($all_days_count == 31) {
                        $repeat_event_conditions[] = "(calendar_events.repeat_event = 'monthly')";
                    } elseif ($all_days_count) {
                        $repeat_event_conditions[] = DB::prepare("(calendar_events.repeat_event = 'monthly' AND DAY(calendar_events.starts_on) IN (?))", $all_days);
                    }
                }
            }

            return "($exact_match OR ($ignore_past_repeting_events AND (" . implode(' OR ', $repeat_event_conditions) . ')))';
        }
    }

    /**
     * Returns true if the two dates are year or more apart.
     *
     * @return bool
     */
    public static function matchWholeYear(DateValue $from, DateValue $to)
    {
        if ($from->getTimestamp() >= $to->getTimestamp()) {
            return false; // Invalid input (from larger or equal than to)
        }

        if ($from->getYear() == $to->getYear()) {
            return $from->getDay() === 1 && $from->getMonth() === 1 && // January 1st
            $to->getDay() === 31 && $to->getMonth() === 12; // December 31st
        } else {
            return $from->getYearday() <= (DateValue::make($to->getMonth(), $to->getDay(), $from->getYear())->getYearday() + 1); // Add one so we fetch situations like 2010/05/12 - 2011/05/11 (we have both 11 and 12 and that makes a whole year)
        }
    }

    /**
     * Return array of weekdays that are affected with this date range.
     *
     * @return array
     */
    public static function matchWeekdays(DateValue $from, DateValue $to)
    {
        if ($from->getTimestamp() < $to->getTimestamp()) {
            if ($to->daysBetween($from) >= 6) {
                return 'any';
            } else {
                $from_clone = clone $from;

                $result = [$from_clone->getWeekday()];

                while (!$from_clone->isSameDay($to)) {
                    $from_clone->advance(86400);
                    $result[] = $from_clone->getWeekday();
                }

                sort($result);

                return $result;
            }
        } else {
            return [$from->getWeekday()];
        }
    }

    /**
     * Convert PHP weekdays to MySQL weekdays (Monday 0, Sunday 6).
     *
     * @param $weekdays
     * @return array
     */
    protected static function phpWeekdaysToMySQLWeekdays($weekdays)
    {
        foreach ($weekdays as $k => $v) {
            if ($v == 0) {
                $weekdays[$k] = 6;
            } else {
                $weekdays[$k] = $v - 1;
            }
        }

        sort($weekdays);

        return $weekdays;
    }

    /**
     * Return array of matching events for given range.
     *
     * This is extracted in a separate function so it can be tested, before any complex queries are build based on the
     * data returned by this code
     *
     * @return array
     */
    public static function matchYearMonthAndDay(DateValue $from, DateValue $to)
    {
        $result = [];

        $from_day = $from->getDay();
        $from_month = $from->getMonth();
        $from_year = $from->getYear();

        $to_day = $to->getDay();
        $to_month = $to->getMonth();
        $to_year = $to->getYear();

        // Same year
        if ($from_year == $to_year) {
            $result[$from_year] = [];

            // Same month
            if ($from_month == $to_month) {
                if ($from_day == 1 && $to_day == self::getLastMonthDay($to_month, $to->isLeapYear())) {
                    $result[$from_year][$from_month] = 'any';
                } else {
                    $result[$from_year][$from_month] = [];

                    for ($i = $from_day; $i <= $to_day; ++$i) {
                        $result[$from_year][$from_month][] = $i;
                    }
                }

            // Different month
            } else {
                // First from month calculation
                if ($from_day == 1) {
                    $result[$from_year][$from_month] = 'any';
                } else {
                    $result[$from_year][$from_month] = [];

                    $last_day = self::getLastMonthDay($from_month, $from->isLeapYear());

                    for ($i = $from_day; $i <= $last_day; ++$i) {
                        $result[$from_year][$from_month][] = $i;
                    }
                }

                // Other months, until the $to_month
                for ($i = $from_month + 1; $i < $to_month; ++$i) {
                    $result[$from_year][$i] = 'any';
                }

                // Dates in tp month

                $last_day = self::getLastMonthDay($to_month, $to->isLeapYear());
                if ($to_day == $last_day) {
                    $result[$from_year][$to_month] = 'any';
                } else {
                    for ($i = 1; $i <= $to_day; ++$i) {
                        $result[$from_year][$to_month][] = $i;
                    }
                }
            }

        // Differnt year
        } else {
            // From day and month
            if ($from_day == 1) {
                $result[$from_year][$from_month] = 'any';
            } else {
                $result[$from_year][$from_month] = [];

                $last_day = self::getLastMonthDay($from_month, $from->isLeapYear());

                for ($i = $from_day; $i <= $last_day; ++$i) {
                    $result[$from_year][$from_month][] = $i;
                }
            }

            if ($from_month < 12) {
                for ($i = $from_month + 1; $i <= 12; ++$i) {
                    $result[$from_year][$i] = 'any';
                }
            }

            // Years in between
            for ($i = $from_year + 1; $i < $to_year; ++$i) {
                $result[$i] = 'any';
            }

            if ($to_month > 1) {
                for ($i = 1; $i < $to_month; ++$i) {
                    $result[$to_year][$i] = 'any';
                }
            }

            if ($to_day == self::getLastMonthDay($to_month, $to->isLeapYear())) {
                $result[$to_year][$to_month] = 'any';
            } else {
                $result[$to_year][$to_month] = [];

                for ($i = 1; $i <= $to_day; ++$i) {
                    $result[$to_year][$to_month][] = $i;
                }
            }
        }

        return $result;
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------

    /**
     * Return number of events in a given date or range.
     *
     * @param  mixed    $date_or_range
     * @param  mixed    $additional_conditions
     * @return DBResult
     */
    public static function countFor($date_or_range, $additional_conditions = null)
    {
        $conditions = CalendarEvents::prepareConditionsBasedOnDateOrRange($date_or_range);

        if ($additional_conditions) {
            $conditions = "($conditions AND (" . DB::prepareConditions($additional_conditions) . '))';
        }

        return DB::executeFirstCell("SELECT COUNT(id) FROM calendar_events WHERE $conditions");
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): CalendarEvent
    {
        self::prepareAttributes($attributes);

        $instance = parent::create($attributes, $save, $announce);

        if ($save && $instance instanceof CalendarEvent) {
            AngieApplication::notifications()
                ->notifyAbout(CalendarsFramework::INJECT_INTO . '/new_calendar_event', $instance, $instance->getCreatedBy())
                ->sendToSubscribers();
        }

        return $instance;
    }

    /**
     * @param DataObject|CalendarEvent $instance
     */
    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): CalendarEvent
    {
        $calendar = $instance->getCalendar();

        if ($save && $calendar instanceof Calendar && isset($attributes['calendar_id']) && $calendar->getId() != $attributes['calendar_id']) {
            $calendar->touch();
        }

        self::prepareAttributes($attributes);

        parent::update($instance, $attributes, $save);

        if ($save && isset($attributes['subscribers']) && is_array($attributes['subscribers']) && !empty($attributes['subscribers'])) {
            /* @var User[] $users */
            $users = Users::findByIds($attributes['subscribers']);

            $instance->setSubscribers($users);

            AngieApplication::notifications()
                ->notifyAbout(CalendarsFramework::INJECT_INTO . '/new_calendar_event', $instance, $instance->getCreatedBy())// @todo(petar) treba da se zameni template sa updated_calendar_event
                ->sendToSubscribers();
        }

        return $instance;
    }

    /**
     * Prepare attributes.
     *
     * @param array &$attributes
     */
    protected static function prepareAttributes(&$attributes)
    {
        if (isset($attributes['starts_on']) && !empty($attributes['starts_on']) && isset($attributes['starts_on_time'])) {
            $starts_on = DateTimeValue::makeFromString($attributes['starts_on'] . ' ' . $attributes['starts_on_time']);
            $gmt_offset = \Angie\Globalization::getUserGmtOffsetOnDate(AngieApplication::authentication()->getLoggedUser(), $starts_on);

            $starts_on->advance($gmt_offset * -1);

            [$date, $time] = explode(' ', $starts_on->toMySQL());
            $attributes['starts_on'] = $date;
            $attributes['starts_on_time'] = $time;
        }

        if (isset($attributes['ends_on']) && !empty($attributes['ends_on']) && isset($attributes['ends_on_time'])) {
            $ends_on = DateTimeValue::makeFromString($attributes['ends_on'] . ' ' . $attributes['ends_on_time']);
            $gmt_offset = \Angie\Globalization::getUserGmtOffsetOnDate(AngieApplication::authentication()->getLoggedUser(), $ends_on);

            $ends_on->advance($gmt_offset * -1);

            [$date, $time] = explode(' ', $ends_on->toMySQL());
            $attributes['ends_on'] = $date;
            $attributes['ends_on_time'] = $time;
        }
    }

    /**
     * Return last day in a given month.
     *
     * @param  int  $month
     * @param  bool $leap_year
     * @return int
     */
    protected static function getLastMonthDay($month, $leap_year = false)
    {
        if ($month == 2) {
            return $leap_year ? 29 : 28;
        } else {
            return in_array($month, [1, 3, 5, 7, 8, 10, 12]) ? 31 : 30;
        }
    }

    /**
     * Delete events by calendar.
     *
     * @param FwCalendar|Calendar $calendar
     */
    public static function deleteByCalendar(Calendar $calendar)
    {
        if ($items = static::find(['conditions' => ['calendar_id = ?', $calendar->getId()]])) {
            foreach ($items as $item) {
                $item->delete();
            }
        }
    }

    /**
     * Return calendar events by calendar.
     *
     * @return CalendarEvents|array
     */
    public static function getByCalendar(Calendar $calendar)
    {
        if ($calendar_event_ids = DB::executeFirstColumn('SELECT id FROM calendar_events WHERE calendar_id = ? AND is_trashed = ?', $calendar->getId(), false)) {
            return CalendarEvents::findByIds($calendar_event_ids);
        }

        return [];
    }
}
