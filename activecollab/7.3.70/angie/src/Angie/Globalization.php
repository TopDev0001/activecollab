<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie;

use AngieApplication;
use ConfigOptions;
use Currencies;
use Currency;
use DateTime;
use DateTimeZone;
use DateValue;
use DayOff;
use DayOffs;
use DB;
use InvalidArgumentException;
use Language;
use Languages;
use User;

final class Globalization
{
    const PAPER_FORMAT_A3 = 'A3';
    const PAPER_FORMAT_A4 = 'A4';
    const PAPER_FORMAT_A5 = 'A5';
    const PAPER_FORMAT_LEGAL = 'Legal';
    const PAPER_FORMAT_LETTER = 'Letter';

    const PAPER_ORIENTATION_PORTRAIT = 'Portrait';
    const PAPER_ORIENTATION_LANDSCAPE = 'Landscape';

    /**
     * Return $content in selected language and insert $params in it.
     *
     * @param  string   $content
     * @param  array    $params
     * @param  bool     $clean_params
     * @param  Language $language
     * @return string
     */
    public static function lang($content, $params = null, $clean_params = true, $language = null)
    {
        $result = self::getTranslationContent($content, $language);

        if ($params && strpos($result, ':') !== false) {
            foreach ($params as $k => $v) {
                $result = str_replace(":$k", ($clean_params ? clean($v) : $v), $result);
            }
        }

        return $result;
    }

    /**
     * Return translation patter for $content and the given language (NULL for current language).
     *
     * @param  string        $content
     * @param  Language|null $language
     * @return string
     */
    public static function getTranslationContent($content, $language = null)
    {
        $locale = $language instanceof Language ? $language->getLocale() : self::$current_language_locale;

        if ($locale == BUILT_IN_LOCALE) {
            return $content;
        } else {
            if ($language instanceof Language && !isset(self::$current_langauge_translations[$locale])) {
                self::$current_langauge_translations[$locale] = $language->getDictionaryTranslations(); // load translations if not loaded already
            }

            return isset(self::$current_langauge_translations[$locale])
                && isset(self::$current_langauge_translations[$locale][$content])
                && self::$current_langauge_translations[$locale][$content]
                ? self::$current_langauge_translations[$locale][$content]
                : $content;
        }
    }

    /**
     * Locale of currenly loaded language.
     *
     * @var string
     */
    private static $current_language_locale = BUILT_IN_LOCALE;

    /**
     * Translations of currently loaded languages.
     *
     * @var array
     */
    private static $current_langauge_translations = [];

    /**
     * Set current locale by given user.
     *
     * @param  User     $user
     * @return Language
     */
    public static function setCurrentLocaleByUser($user)
    {
        $language = $user instanceof User ? $user->getLanguage() : Languages::findDefault();

        if ($language instanceof Language) {
            self::$current_language_locale = $language->getLocale();

            if (self::$current_language_locale != BUILT_IN_LOCALE) {
                setlocale(LC_ALL, self::$current_language_locale); // Set locale
                self::$current_langauge_translations[self::$current_language_locale] = $language->getDictionaryTranslations();
            }

            return $language;
        }

        return new Language();
    }

    /**
     * Return array of month names.
     *
     * @param  Language $language
     * @return array
     */
    public static function getMonthNames($language = null)
    {
        return [
            1 => lang('January', null, null, $language),
            2 => lang('February', null, null, $language),
            3 => lang('March', null, null, $language),
            4 => lang('April', null, null, $language),
            5 => lang('May', null, null, $language),
            6 => lang('June', null, null, $language),
            7 => lang('July', null, null, $language),
            8 => lang('August', null, null, $language),
            9 => lang('September', null, null, $language),
            10 => lang('October', null, null, $language),
            11 => lang('November', null, null, $language),
            12 => lang('December', null, null, $language),
        ];
    }

    /**
     * Return array of month names, in short format.
     *
     * @param  Language $language
     * @return array
     */
    public static function getShortMonthNames($language = null)
    {
        return [
            1 => lang('Jan', null, null, $language),
            2 => lang('Feb', null, null, $language),
            3 => lang('Mar', null, null, $language),
            4 => lang('Apr', null, null, $language),
            5 => lang('May', null, null, $language),
            6 => lang('Jun', null, null, $language),
            7 => lang('Jul', null, null, $language),
            8 => lang('Aug', null, null, $language),
            9 => lang('Sep', null, null, $language),
            10 => lang('Oct', null, null, $language),
            11 => lang('Nov', null, null, $language),
            12 => lang('Dec', null, null, $language),
        ];
    }

    /**
     * Return name of the month.
     *
     * @param  int      $month
     * @param  bool     $short
     * @param  Language $language
     * @return string
     */
    public static function getMonthName($month, $short = false, $language = null)
    {
        $month_names = $short ? self::getShortMonthNames($language) : self::getMonthNames($language);

        return isset($month_names[$month]) ? $month_names[$month] : lang('Unknown', null, true, $language);
    }

    /**
     * Return day names.
     *
     * @param  Language $language
     * @return array
     */
    public static function getDayNames($language = null)
    {
        return [
            0 => lang('Sunday', null, null, $language),
            1 => lang('Monday', null, null, $language),
            2 => lang('Tuesday', null, null, $language),
            3 => lang('Wednesday', null, null, $language),
            4 => lang('Thursday', null, null, $language),
            5 => lang('Friday', null, null, $language),
            6 => lang('Saturday', null, null, $language),
        ];
    }

    /**
     * Return short day names.
     *
     * @param  Language $language
     * @return array
     */
    public static function getShortDayNames($language = null)
    {
        return [
            0 => lang('Sun', null, null, $language),
            1 => lang('Mon', null, null, $language),
            2 => lang('Tue', null, null, $language),
            3 => lang('Wed', null, null, $language),
            4 => lang('Thu', null, null, $language),
            5 => lang('Fri', null, null, $language),
            6 => lang('Sat', null, null, $language),
        ];
    }

    /**
     * Return name of the day.
     *
     * @param  int      $day
     * @param  bool     $short
     * @param  Language $language
     * @return string
     */
    public static function getDayName($day, $short = false, $language = null)
    {
        $day_names = $short ? self::getShortDayNames($language) : self::getDayNames($language);

        return isset($day_names[$day]) ? $day_names[$day] : lang('Unknown', null, true, $language);
    }

    /**
     * Returns true if $date is work day.
     *
     * @return bool
     */
    public static function isWorkday(DateValue $date)
    {
        return in_array($date->getWeekday(), self::getWorkDays());
    }

    /**
     * Returns true if $date is not work day.
     *
     * @return bool
     */
    public static function isWeekend(DateValue $date)
    {
        return !self::isWorkday($date);
    }

    /**
     * Returns true if $date is day off.
     *
     * @return bool
     */
    public static function isDayOff(DateValue $date)
    {
        $escaped_date = DB::escape($date);

        // Quick and exact
        if (DB::executeFirstCell("SELECT COUNT(id) FROM day_offs WHERE $escaped_date BETWEEN start_date AND end_date")) {
            return true;
        }

        /** @var DayOff[] $days_off * */
        $days_off = DayOffs::find(['conditions' => ['repeat_yearly = ?', true]]);

        if ($days_off) {
            foreach ($days_off as $day_off) {
                $start_date = $day_off->getStartDate();

                if ($start_date->getYear() > $date->getYear()) {
                    continue; // Repeating events starts after $date
                }

                $end_date = $day_off->getEndDate();

                if ($day_off->isMultiDay()) {
                    // Range spans between two years
                    if ($start_date->getYear() != $end_date->getYear()) {
                        // Lets probe previous and current year for range match
                        foreach ([$date->getYear() - 1, $date->getYear()] as $year_to_test) {
                            $start_date->setYear($year_to_test);
                            $end_date->setYear($year_to_test + 1);

                            if ($date->getTimestamp() >= $start_date->getTimestamp() && $date->getTimestamp() <= $end_date->getTimestamp()) {
                                return true;
                            }
                        }

                        // Range between days in the same year
                    } else {
                        $tmp_date = clone $date;

                        $tmp_date->setYear($start_date->getYear());

                        if ($tmp_date->getTimestamp() >= $start_date->getTimestamp() && $tmp_date->getTimestamp() <= $end_date->getTimestamp()) {
                            return true;
                        }
                    }
                } else {
                    if ($day_off->getStartDate()->getMonth() == $date->getMonth() && $day_off->getStartDate()->getDay() == $date->getDay()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get array of workdays.
     *
     * @return array
     */
    public static function getWorkdays()
    {
        return ConfigOptions::getValue('time_workdays');
    }

    /**
     * Get array of days off.
     *
     * @return array
     */
    public static function getDaysOff()
    {
        return DayOffs::find();
    }

    /**
     * Get array of letters in english alphabet.
     *
     * @return array
     */
    public static function getAlphabet()
    {
        return ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
    }

    /**
     * Cached decimal separator for logged user.
     *
     * @var string
     */
    private static $logged_user_decimal_separator = false;
    private static $logged_user_thousands_separator = false;

    /**
     * Get number sepaarators for logged users.
     *
     * @return string[]
     */
    public static function getNumberSeparators()
    {
        if (self::$logged_user_decimal_separator == false) {
            $logged_user = AngieApplication::authentication()->getAuthenticatedUser();
            if ($logged_user instanceof User) {
                $language = $logged_user->getLanguage();
            } else {
                $language = Languages::findDefault();
            }

            self::$logged_user_decimal_separator = $language instanceof Language ? $language->getDecimalSeparator() : '.';
            self::$logged_user_thousands_separator = $language instanceof Language ? $language->getThousandsSeparator() : '';
        }

        return [
            self::$logged_user_decimal_separator,
            self::$logged_user_thousands_separator,
        ];
    }

    /**
     * Format number.
     *
     * @param  float  $number
     * @param  int    $decimal_spaces
     * @param  bool   $trim_zeros
     * @return string
     */
    public static function formatNumber($number, Language $language = null, $decimal_spaces = 2, $trim_zeros = false)
    {
        if ($language instanceof Language) {
            $decimal_separator = $language->getDecimalSeparator();
            $thousands_separator = $language->getThousandsSeparator();
        } else {
            [$decimal_separator, $thousands_separator] = self::getNumberSeparators();
        }

        $formatted_number = number_format($number, $decimal_spaces, $decimal_separator, $thousands_separator);

        if ($trim_zeros) {
            $formatted_number = rtrim(trim($formatted_number, 0), $decimal_separator);

            if ($formatted_number && substr($formatted_number, 0, 1) == $decimal_separator) {
                $formatted_number = '0' . $formatted_number;
            }
        }

        return $formatted_number;
    }

    public static function formatMoney(
        $number,
        Currency $currency = null,
        Language $language = null,
        bool $include_code = false,
        bool $round = false
    ): string
    {
        // get default currency
        if (!$currency instanceof Currency) {
            $currency = Currencies::getDefault();
        }

        // if we need to round money
        if ($round && $currency->getDecimalRounding()) {
            $number = Currencies::roundDecimal($number, $currency);
        }

        $formatted = self::formatNumber($number, $language, $currency->getDecimalSpaces());

        if ($include_code) {
            if (strtoupper($currency->getCode()) === 'USD') {
                $formatted = $currency->getCode() . ' ' . $formatted;
            } elseif ($currency->getCode() === '$') {
                $formatted = $currency->getCode() . $formatted;
            } else {
                $formatted = $formatted . ' ' . $currency->getCode();
            }
        }

        return $formatted;
    }

    /**
     * Cached system GMT offset.
     *
     * @var int
     */
    private static $gmt_offset = false;

    /**
     * Return system GMT offset.
     *
     * @param  bool $reload
     * @return int
     */
    public static function getGmtOffset($reload = false)
    {
        if ($reload || self::$gmt_offset === false) {
            self::$gmt_offset = self::timezoneToGmtOffset(
                ConfigOptions::getValue('time_timezone', !$reload),
                DateValue::now()
            );
        }

        return self::$gmt_offset;
    }

    /**
     * Cached GMT offsets on date.
     *
     * @var array
     */
    private static $gmt_offset_on_date = [];

    /**
     * @param  bool $reload
     * @return int
     */
    public static function getGmtOffsetOnDate(DateValue $date = null, $reload = false)
    {
        if ($date === null) {
            $date = new DateValue();
        }

        if ($reload || !array_key_exists($date->toMySQL(), self::$gmt_offset_on_date)) {
            self::$gmt_offset_on_date[$date->toMySQL()] = self::timezoneToGmtOffset(ConfigOptions::getValue('time_timezone', !$reload), $date);
        }

        return self::$gmt_offset_on_date[$date->toMySQL()];
    }

    /**
     * Cached user GMT offsets.
     *
     * @var int[]
     */
    private static array $user_gmt_offset = [];

    /**
     * Return user GMT offset.
     *
     * @param User|null $user
     * @param bool      $reload
     */
    public static function getUserGmtOffset($user = null, $reload = false): int
    {
        if ($user === null) {
            $user = AngieApplication::authentication()->getAuthenticatedUser();
        }

        if ($user instanceof User) {
            if ($reload || !array_key_exists($user->getId(), self::$user_gmt_offset)) {
                self::$user_gmt_offset[$user->getId()] = self::timezoneToGmtOffset(
                    ConfigOptions::getValueFor('time_timezone', $user)
                );
            }

            return self::$user_gmt_offset[$user->getId()];
        }

        return self::getGmtOffset($reload);
    }

    /**
     * Return user GMT offset on a given date.
     *
     * @param User $user
     */
    public static function getUserGmtOffsetOnDate($user, DateValue $date): int
    {
        return self::timezoneToGmtOffset(ConfigOptions::getValueFor('time_timezone', $user), $date);
    }

    /**
     * Get GMT offset of a given time zone.
     *
     * @param string         $timezone
     * @param DateValue|null $date
     */
    private static function timezoneToGmtOffset($timezone, $date = null): int
    {
        if (empty($timezone) || $timezone === 'Etc/Unknown') {
            $timezone = 'UTC';
        }

        $reference = $date instanceof DateValue ? new DateTime($date->toMySQL()) : new DateTime();

        return (new DateTimeZone($timezone))->getOffset($reference);
    }

    /**
     * @return DateValue[]
     */
    public static function getNonWorkingDaysBetweenDates(DateValue $first, DateValue $second): array
    {
        if ($first->getTimestamp() > $second->getTimestamp()) {
            throw new InvalidArgumentException('The second date must be greater than the first.');
        }

        $result = [];

        do {
            if ($first->isWeekend() || $first->isDayOff()) {
                $result[] = clone $first;
            }
            $first->addDays(1);
        } while ($first->getTimestamp() < $second->getTimestamp());

        if ($second->isWeekend() || $second->isDayOff()) {
            $result[] = clone $second;
        }

        return $result;
    }

    /**
     * @return DateValue[]
     */
    public static function getWorkingDaysBetweenDates(DateValue $first, DateValue $second): array
    {
        if ($first->getTimestamp() > $second->getTimestamp()) {
            throw new InvalidArgumentException('The second date must be greater than the first.');
        }

        $result = [];

        do {
            if (!$first->isWeekend() && !$first->isDayOff()) {
                $result[] = clone $first;
            }
            $first->addDays(1);
        } while ($first->getTimestamp() < $second->getTimestamp());

        if (!$second->isWeekend() && !$second->isDayOff()) {
            $result[] = clone $second;
        }

        return $result;
    }
}
