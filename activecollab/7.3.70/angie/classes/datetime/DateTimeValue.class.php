<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;

/**
 * Single date time value.
 *
 * This class provides some handy methods for working with timestamps and extracting data from them
 *
 * @package angie.library.datetime
 */
class DateTimeValue extends DateValue
{
    /**
     * @var int
     */
    protected $hour;

    /**
     * @var int
     */
    protected $minute;

    /**
     * @var int
     */
    protected $second;

    // ---------------------------------------------------
    //  Static methods
    // ---------------------------------------------------

    /**
     * Returns current time object.
     *
     * @return DateTimeValue
     */
    public static function now()
    {
        return new self(self::getCurrentTimestamp());
    }

    /**
     * This function works like mktime, just it always returns GMT.
     *
     * @param  int           $hour
     * @param  int           $minute
     * @param  int           $second
     * @param  int           $month
     * @param  int           $day
     * @param  int           $year
     * @return DateTimeValue
     */
    public static function make($month, $day, $year, $hour = null, $minute = null, $second = null)
    {
        return new self(mktime($hour, $minute, $second, $month, $day, $year));
    }

    /**
     * Make instance from timestamp.
     *
     * @param  int           $timestamp
     * @return DateTimeValue
     */
    public static function makeFromTimestamp($timestamp)
    {
        return new self($timestamp);
    }

    /**
     * Make time from string using strtotime() function. This function will return null
     * if it fails to convert string to the time.
     *
     * @param  string        $str
     * @return DateTimeValue
     */
    public static function makeFromString($str)
    {
        $timestamp = strtotime($str);

        return ($timestamp === false) || ($timestamp === -1) ? null : new self($timestamp);
    }

    /**
     * Get System Timezone DateValue.
     *
     * @return DateValue
     */
    public function getSystemDate()
    {
        $corrected_date_time = $this->advance(
            Globalization::getGmtOffset(
                AngieApplication::isInTestMode() // Make sure that we reload time zone offset while we are in test
            ),
            false
        );

        return DateValue::makeFromString($corrected_date_time->format('Y-m-d'));
    }

    /**
     * Set hour, minutes and seconds with one method call.
     *
     * @param  int   $hour
     * @param  int   $minutes
     * @param  int   $seconds
     * @return $this
     */
    public function &setTime($hour, $minutes, $seconds)
    {
        $this->setHour($hour);
        $this->setMinute($minutes);
        $this->setSecond($seconds);

        return $this;
    }

    /**
     * Return beginning of the month DateTimeValue.
     *
     * @param  int           $month
     * @param  int           $year
     * @return DateTimeValue
     */
    public static function beginningOfMonth($month, $year)
    {
        return new self("$year-$month-1 00:00:00");
    }

    /**
     * Return end of the month.
     *
     * @param  int           $month
     * @param  int           $year
     * @return DateTimeValue
     */
    public static function endOfMonth($month, $year)
    {
        $reference = mktime(0, 0, 0, $month, 15, $year);
        $last_day = date('t', $reference);

        return new self("$year-$month-$last_day 23:59:59");
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
            return new self($this->getTimestamp() + Globalization::getUserGmtOffset($user));
        } else {
            return clone $this;
        }
    }

    /**
     * Return valid DateTimeValue offset by given user's time zone in GMT.
     *
     * @param  IUser     $user
     * @return DateValue
     */
    public function getForUserInGMT($user = null)
    {
        if ($user instanceof IUser) {
            return new self($this->getTimestamp() - Globalization::getUserGmtOffset($user));
        } else {
            return clone $this;
        }
    }

    // ---------------------------------------------------
    //  Formating
    // ---------------------------------------------------

    /**
     * Format date for user.
     *
     * @param  IUser         $user
     * @param  int           $offset
     * @param  Language|null $language
     * @return string
     */
    public function formatDateForUser($user = null, $offset = null, $language = null)
    {
        return parent::formatForUser($user, $offset, $language);
    }

    /**
     * Format value for given user.
     *
     * @param  IUser         $user
     * @param  int           $offset
     * @param  Language|null $language
     * @return string
     */
    public function formatForUser($user = null, $offset = null, $language = null)
    {
        $user = $user instanceof IUser ? $user : AngieApplication::authentication()->getLoggedUser();

        if ($user instanceof IUser) {
            $format = $user->getDateTimeFormat();
        } else {
            $format = FORMAT_DATETIME;
        }

        return $this->formatUsingStrftime(
            $format,
            $offset === null ? Globalization::getUserGmtOffset($user) : (int) $offset,
            $language
        );
    }

    /**
     * Format time for user.
     *
     * @param  IUser         $user
     * @param  int           $offset
     * @param  Language|null $language
     * @return string
     */
    public function formatTimeForUser($user = null, $offset = null, $language = null)
    {
        $user = $user instanceof IUser ? $user : AngieApplication::authentication()->getAuthenticatedUser();

        if ($user instanceof IUser) {
            $format = $user->getTimeFormat();
        } else {
            $format = FORMAT_TIME;
        }

        return $this->formatUsingStrftime(
            $format,
            $offset === null ? Globalization::getUserGmtOffset($user) : (int) $offset,
            $language
        );
    }

    /**
     * Return datetime formated in MySQL datetime format.
     *
     * @return string
     */
    public function toMySQL()
    {
        return $this->format(DATETIME_MYSQL);
    }

    /**
     * Return only date part in MySQL format.
     *
     * @return string
     */
    public function dateToMySQL()
    {
        return $this->format(DATE_MYSQL);
    }

    /**
     * Return padded hour value.
     *
     * @return string
     */
    public function paddedHour()
    {
        return str_pad($this->getHour(), 2, '0');
    }

    /**
     * Return hour.
     *
     * @return int
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * Set hour value.
     *
     * @param int $value
     */
    public function setHour($value)
    {
        $this->hour = (int) $value;
        $this->setTimestampFromAttributes();
    }

    // ---------------------------------------------------
    //  Utils
    // ---------------------------------------------------

    /**
     * Set timestamp value.
     *
     * @param int $value
     */
    public function setTimestamp($value)
    {
        $this->timestamp = $value;
        $this->parse();
    }

    /**
     * Update internal timestamp based on internal param values.
     */
    public function setTimestampFromAttributes()
    {
        $this->setTimestamp(
            mktime(
                $this->hour,
                $this->minute,
                $this->second,
                $this->month,
                $this->day,
                $this->year
            )
        );
    }

    /**
     * Return padded minute value.
     *
     * @return string
     */
    public function paddedMinute()
    {
        return str_pad($this->getMinute(), 2, '0');
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Return minute.
     *
     * @return int
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Return padded seconds value.
     *
     * @return string
     */
    public function paddedSecond()
    {
        return str_pad($this->getSecond(), 2, '0');
    }

    /**
     * Return seconds.
     *
     * @return int
     */
    public function getSecond()
    {
        return $this->second;
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
            $this->hour = (int) $this->date_data['hours'];
            $this->minute = (int) $this->date_data['minutes'];
            $this->second = (int) $this->date_data['seconds'];
        }
    }

    /**
     * Set minutes value.
     *
     * @param int $value
     */
    public function setMinute($value)
    {
        $this->minute = (int) $value;
        $this->setTimestampFromAttributes();
    }

    /**
     * Set seconds.
     *
     * @param int $value
     */
    public function setSecond($value)
    {
        $this->second = (int) $value;
        $this->setTimestampFromAttributes();
    }
}
