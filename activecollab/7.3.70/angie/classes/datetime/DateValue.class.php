<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;

/**
 * Data value object.
 *
 * Instance of this class represents single date (time part is ignored)
 *
 * @package angie.library.datetime
 */
class DateValue implements JsonSerializable
{
    /**
     * Locked current timestamp value.
     *
     * @var int|null
     */
    private static $current_timestamp = null;

    /**
     * Internal timestamp value.
     *
     * @var int
     */
    protected $timestamp;

    /**
     * Cached day value.
     *
     * @var int
     */
    protected $day;

    /**
     * Cached month value.
     *
     * @var int
     */
    protected $month;

    /**
     * Cached year value.
     *
     * @var int
     */
    protected $year;

    // ---------------------------------------------------
    //  Static methods
    // ---------------------------------------------------
    /**
     * Date data, result of getdate() function.
     *
     * @var array
     */
    protected $date_data;

    /**
     * Construct the DateValue.
     *
     * @param int $timestamp
     */
    public function __construct($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = static::getCurrentTimestamp();
        } elseif (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        $this->setTimestamp($timestamp);
    }

    /**
     * Returns today object.
     *
     * @return DateValue
     */
    public static function now()
    {
        return new DateValue(DateValue::getCurrentTimestamp());
    }

    /**
     * Return current timestamp.
     *
     * @return int
     */
    public static function getCurrentTimestamp()
    {
        return self::$current_timestamp ? self::$current_timestamp : time();
    }

    /**
     * Return true if current timestamp is locked.
     *
     * @return bool
     */
    public static function isCurrentTimestampLocked()
    {
        return self::$current_timestamp !== null;
    }

    /**
     * Lock current timestamp to a given value.
     *
     * If $timestamp is set, that value will be used. In case $timestamp is null, current timestamp (time() call) will be used
     *
     * @param int|string|null $timestamp
     */
    public static function lockCurrentTimestamp($timestamp = null): int
    {
        if (is_int($timestamp)) {
            self::$current_timestamp = $timestamp;
        } elseif (is_string($timestamp)) {
            $string_to_timestamp = strtotime($timestamp);

            if ($string_to_timestamp === false) {
                throw new InvalidArgumentException(sprintf('Failed to parse "%s" to timestamp.', $timestamp));
            }

            self::$current_timestamp = $string_to_timestamp;
        } elseif ($timestamp === null) {
            self::$current_timestamp = time();
        } else {
            throw new InvalidParamError('timestamp', $timestamp, 'Timestamp can be a valid timestamp, or NULL.');
        }

        return self::$current_timestamp;
    }

    /**
     * Unlock current timestamp.
     */
    public static function unlockCurrentTimestamp()
    {
        self::$current_timestamp = null;
    }

    /**
     * This function works like mktime, just it always returns GMT.
     *
     * @param  int       $month
     * @param  int       $day
     * @param  int       $year
     * @return DateValue
     */
    public static function make($month, $day, $year)
    {
        return new DateValue(mktime(0, 0, 0, $month, $day, $year));
    }

    /**
     * Make instance from timestamp.
     *
     * @param  int       $timestamp
     * @return DateValue
     */
    public static function makeFromTimestamp($timestamp)
    {
        return new DateValue($timestamp);
    }

    /**
     * Make time from string using strtotime() function. This function will
     * return null if it fails to convert string to the time.
     *
     * @param  string         $str
     * @return DateValue|null
     */
    public static function makeFromString($str)
    {
        if (empty($str)) {
            return null;
        }

        $timestamp = strtotime($str);

        return ($timestamp === false) || ($timestamp === -1) ? null : new DateValue($timestamp);
    }

    /**
     * Return beginning of the month DateTimeValue.
     *
     * @param  int       $month
     * @param  int       $year
     * @return DateValue
     */
    public static function beginningOfMonth($month, $year)
    {
        return new DateValue("$year-$month-1 00:00:00");
    }

    /**
     * Return end of the month.
     *
     * @param  int       $month
     * @param  int       $year
     * @return DateValue
     */
    public static function endOfMonth($month, $year)
    {
        $reference = mktime(0, 0, 0, $month, 15, $year);
        $last_day = date('t', $reference);

        return new DateValue("$year-$month-$last_day");
    }

    // ---------------------------------------------------
    //  Instance methods
    // ---------------------------------------------------

    public static function iterateDaily(DateValue $from, DateValue $to, callable $callback): void
    {
        $from_date = new DateTime($from->toMySQL());
        $to_date = new DateTime($to->advance(86400)->toMySQL());

        $interval = DateInterval::createFromDateString('1 day');

        /** @var DateTime $date */
        foreach (new DatePeriod($from_date, $interval, $to_date) as $date) {
            call_user_func($callback, new DateValue($date->format('Y-m-d')));
        }
    }

    /**
     * Loop through weeks from $from date to $to date and call $callback with $from_date, $to_date, $year and $week
     * parameters.
     *
     * @param Closure $callback
     * @param int     $first_week_day
     */
    public static function iterateWeekly(DateValue $from, DateValue $to, $callback, $first_week_day = 0)
    {
        $start_from = $from->beginningOfWeek($first_week_day);
        $to_the_end = $to->endOfWeek($first_week_day);

        /** @var DateTime $date */
        foreach (new DatePeriod(new DateTime($start_from->toMySQL()), new DateInterval('P1W'), new DateTime($to_the_end->toMySQL())) as $date) {
            $week_start = new DateTimeValue($date->getTimestamp());
            $week_end = $week_start->endOfWeek($first_week_day);

            $callback->__invoke($week_start, $week_end);
        }
    }

    /**
     * Return beginning of week object.
     *
     * @param  int           $first_week_day
     * @return DateTimeValue
     */
    public function beginningOfWeek($first_week_day = 0)
    {
        $weekday = $this->getWeekday();
        if ($weekday >= $first_week_day) {
            $days_delta = $weekday - $first_week_day;
        } else {
            $days_delta = $weekday - $first_week_day + 7;
        }

        return $this->beginningOfDay()->advance($days_delta * -86400, false);
    }

    /**
     * Adds (or subtracts) days to or from current date. Negative value for subtraction.
     *
     * @param  int                     $days_delta
     * @param  bool                    $mutate
     * @return DateValue|DateTimeValue
     */
    public function addDays($days_delta = 0, $mutate = true)
    {
        return $this->advance($days_delta * 86400, $mutate);
    }

    /**
     * Return weeekday for given date.
     *
     * @return int
     */
    public function getWeekday()
    {
        return isset($this->date_data['wday']) ? $this->date_data['wday'] : null;
    }

    /**
     * Advance for specific time.
     *
     * If $mutate is true value of this object will be changed. If false a new
     * DateValue or DateTimeValue instance will be returned with timestamp
     * moved for $input number of seconds
     *
     * @param  int                     $input
     * @param  bool                    $mutate
     * @return DateValue|DateTimeValue
     */
    public function advance($input, $mutate = true)
    {
        $timestamp = (int) $input;

        // https://en.wikipedia.org/wiki/Year_2038_problem
        if ($this->getTimestamp() + $timestamp >= PHP_INT_MAX) {
            $this->setTimestamp(PHP_INT_MAX);

            return $this;
        }

        if ($mutate) {
            $this->setTimestamp($this->getTimestamp() + $timestamp);

            return $this;
        }

        if ($this instanceof DateTimeValue) {
            return new DateTimeValue($this->getTimestamp() + $timestamp);
        }

        return new DateValue($this->getTimestamp() + $timestamp);
    }

    /**
     * Get timestamp.
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set timestamp value.
     *
     * @param int $value
     */
    public function setTimestamp($value)
    {
        // Make sure that timestamp is always reseted to 00:00:00 of the given date.
        $this->timestamp = strtotime(date('Y-m-d', $value));

        $this->parse();
    }

    /**
     * This function will move internal data to the beginning of day and return modified object.
     *
     * @return DateTimeValue
     */
    public function beginningOfDay()
    {
        return new DateTimeValue(
            mktime(
                0,
                0,
                0,
                $this->getMonth(),
                $this->getDay(),
                $this->getYear(),
            ),
        );
    }

    /**
     * Return numberic representation of month.
     *
     * @return int
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set month value.
     *
     * @param int $value
     */
    public function setMonth($value)
    {
        $this->month = (int) $value;
        $this->setTimestampFromAttributes();
    }

    /**
     * Return days.
     *
     * @return int
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Set day value.
     *
     * @param int $value
     */
    public function setDay($value)
    {
        $this->day = (int) $value;
        $this->setTimestampFromAttributes();
    }

    /**
     * Return year.
     *
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set year value.
     *
     * @param int $value
     */
    public function setYear($value)
    {
        $this->year = (int) $value;
        $this->setTimestampFromAttributes();
    }

    /**
     * Return end of week date time object.
     *
     * @param  int           $first_week_day
     * @return DateTimeValue
     */
    public function endOfWeek($first_week_day = 0)
    {
        return $this->beginningOfWeek($first_week_day)->advance(604799, false);
    }

    /**
     * Update internal timestamp based on internal param values.
     */
    public function setTimestampFromAttributes()
    {
        $this->setTimestamp(mktime(0, 0, 0, $this->month, $this->day, $this->year));
    }

    /**
     * Returns true if this date is in range of given dates.
     *
     * @return bool
     */
    public function inRange(DateValue $from, DateValue $to)
    {
        return ($this->getTimestamp() >= $from->getTimestamp()) && ($this->getTimestamp() <= $to->getTimestamp());
    }

    /**
     * Returns true if $value falls on the same day as this day.
     *
     * @return bool
     */
    public function isSameDay(DateValue $value)
    {
        return ($value->getDay() == $this->getDay()) && ($value->getMonth() == $this->getMonth()) && ($value->getYear() == $this->getYear());
    }

    /**
     * This function will return true if this date object is yesterday.
     *
     * @param  int  $offset
     * @return bool
     */
    public function isYesterday($offset = null)
    {
        return $this->isToday($offset - 86400);
    }

    public function getQuarter(): int
    {
        if ($this->getMonth() <= 3) {
            return 1;
        } elseif ($this->getMonth() <= 6) {
            return 2;
        } elseif ($this->getMonth() <= 9) {
            return 3;
        } else {
            return 4;
        }
    }

    // ---------------------------------------------------
    //  Format to some standard values
    // ---------------------------------------------------

    /**
     * This function will return true if this day is today.
     *
     * @param  int  $offset
     * @return bool
     */
    public function isToday($offset = null)
    {
        $today = new DateTimeValue(time() + $offset);
        $today->beginningOfDay();

        return $this->getDay() == $today->getDay() && $this->getMonth() == $today->getMonth() && $this->getYear() == $today->getYear();
    }

    /**
     * Returns true if this date object is tomorrow.
     *
     * @param  int  $offset
     * @return bool
     */
    public function isTomorrow($offset = null)
    {
        return $this->isToday($offset + 86400);
    }

    /**
     * Is this a weekend day.
     *
     * @return bool
     */
    public function isWeekend()
    {
        return Globalization::isWeekend($this);
    }

    /**
     * Returns true if this date is workday.
     *
     * @return bool
     */
    public function isWorkday()
    {
        return Globalization::isWorkday($this);
    }

    /**
     * Returns true if this date is holiday.
     *
     * @return bool
     */
    public function isDayOff()
    {
        return Globalization::isDayOff($this);
    }

    /**
     * Returns if year is leap year.
     *
     * @return bool
     */
    public function isLeapYear()
    {
        return ($this->getYear() % 4) === 0;
    }

    /**
     * This function will set hours, minutes and seconds to 23:59:59 and return
     * this object.
     *
     * If you wish to get end of this day simply type:
     *
     * @return DateTimeValue
     */
    public function endOfDay()
    {
        return new DateTimeValue(mktime(23, 59, 59, $this->getMonth(), $this->getDay(), $this->getYear()));
    }

    /**
     * Calculate difference in days between this day and $second date.
     *
     * @return int
     */
    public function daysBetween(DateValue $second)
    {
        $first_timestamp = mktime(12, 0, 0, $this->getMonth(), $this->getDay(), $this->getYear());
        $second_timestamp = mktime(12, 0, 0, $second->getMonth(), $second->getDay(), $second->getYear());

        if ($first_timestamp == $second_timestamp) {
            return 0;
        }

        $diff = (int) abs($first_timestamp - $second_timestamp);

        if ($diff < 86400) {
            return $this->getDay() != $second->getDay() ? 1 : 0;
        }

        return (int) round($diff / 86400);
    }

    /**
     * Return valid DateValue offset by given user's time zone.
     *
     * @param  IUser     $user
     * @return DateValue
     */
    public function getForUser($user = null)
    {
        if ($user instanceof IUser) {
            return new DateValue($this->getTimestamp() + Globalization::getUserGmtOffset($user));
        }

        return clone $this;
    }

    /**
     * Return valid DateValue offset by given user's time zone in GMT.
     *
     * @param  IUser     $user
     * @return DateValue
     */
    public function getForUserInGMT($user = null)
    {
        if ($user instanceof IUser) {
            return new DateValue($this->getTimestamp() - Globalization::getUserGmtOffset($user));
        }

        return clone $this;
    }

    // ---------------------------------------------------
    //  Utils
    // ---------------------------------------------------

    /**
     * Format Date For user.
     *
     * @param  IUser|null $user
     * @param  int        $offset
     * @param  Language   $language
     * @return string
     */
    public function formatDateForUser($user = null, $offset = null, $language = null)
    {
        return $this->formatForUser($user, $offset, $language);
    }

    /**
     * Format value for given user.
     *
     * @param  IUser|null    $user
     * @param  int           $offset
     * @param  Language|null $language
     * @return string
     */
    public function formatForUser($user = null, $offset = null, $language = null)
    {
        $user = $user instanceof IUser ? $user : AngieApplication::authentication()->getAuthenticatedUser();

        if ($user instanceof IUser) {
            $format = $user->getDateFormat();
        } else {
            $format = FORMAT_DATE;
        }

        return $this->formatUsingStrftime(
            $format,
            $offset === null ? Globalization::getUserGmtOffset($user) : (int) $offset,
            $language,
        );
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Return ISO8601 formated time.
     *
     * @return string
     */
    public function toISO8601()
    {
        return $this->format(DATE_ISO8601);
    }

    /**
     * Return formated datetime.
     *
     * @param  string $format
     * @return string
     */
    public function format($format)
    {
        return date($format, $this->getTimestamp());
    }

    /**
     * Return formated datetime.
     *
     * @param  string        $format
     * @param  Language|null $language
     * @return string
     */
    public function formatUsingStrftime($format, int $offset = null, $language = null)
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            $format = str_replace('%e', '%d', $format);
        }

        $timestamp = $this->getTimestamp();

        if ($offset !== null) {
            $timestamp += $offset;
        }

        if ($language instanceof Language) {
            $bits = getdate($timestamp);

            $short_month_name = Globalization::getMonthName($bits['mon'], true, $language); // Used twice, for %b and %h

            $replacements = [
                '%a' => Globalization::getDayName($bits['wday'], true, $language),
                '%A' => Globalization::getDayName($bits['wday'], false, $language),
                '%b' => $short_month_name,
                '%B' => Globalization::getMonthName($bits['mon'], false, $language),
                '%h' => $short_month_name,
            ];

            $format = str_replace(array_keys($replacements), array_values($replacements), $format);
        }

        return @strftime($format, $timestamp);
    }

    /**
     * Return atom formated time (W3C format).
     *
     * @return string
     */
    public function toAtom()
    {
        return $this->format(DATE_ATOM);
    }

    /**
     * Return RSS format.
     *
     * @return string
     */
    public function toRSS()
    {
        return $this->format(DATE_RSS);
    }

    /**
     * Return iCalendar formated date and time.
     *
     * @return string
     */
    public function toICalendar()
    {
        return $this->format('Ymd\THis\Z');
    }

    /**
     * Describe for feather.
     */
    public function jsonSerialize(): int
    {
        return $this->getTimestamp();
    }

    /**
     * Break timestamp into its parts and set internal variables.
     */
    public function parse()
    {
        $this->date_data = getdate($this->timestamp);

        if ($this->date_data) {
            $this->year = (int) $this->date_data['year'];
            $this->month = (int) $this->date_data['mon'];
            $this->day = (int) $this->date_data['mday'];
        }
    }

    /**
     * Return yearday from given date.
     *
     * @return int
     */
    public function getYearday()
    {
        return isset($this->date_data['yday']) ? $this->date_data['yday'] : null;
    }

    /**
     * Return year week.
     *
     * @return int
     */
    public function getWeek()
    {
        return (int) date('W', $this->getTimestamp());
    }

    /**
     * Return ISO value.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toMySQL();
    }

    /**
     * Return datetime formated in MySQL datetime format.
     *
     * @return string
     */
    public function toMySQL()
    {
        return $this->format(DATE_MYSQL);
    }
}
