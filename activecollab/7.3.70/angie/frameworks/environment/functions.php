<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * Globalization related functions.
 *
 * @package angie.frameworks.globalization
 */
use Angie\Globalization;

/**
 * Shortcut function to Globalization::lang().
 *
 * @param  string   $content
 * @param  array    $params
 * @param  bool     $clean_params
 * @param  Language $language
 * @return string
 */
function lang($content, $params = null, $clean_params = true, $language = null)
{
    return Globalization::lang($content, $params, $clean_params, $language);
}

/**
 * Convert time to float Value.
 *
 * @param  string $time
 * @return float
 */
function time_to_float($time)
{
    switch (substr_count_utf($time, ':')) {
        case 0:
            if (strpos($time, ',') !== false) {
                $time = str_replace(',', '.', $time);
            }

            if (substr_count($time, '.') > 1) {
                throw new InvalidArgumentException(
                    sprintf('"%s" is not a properly formatted time value.', $time),
                );
            }

            return (float) $time;
        case 1:
            $time_arr = explode(':', $time);

            if (empty($time_arr[0])) {
                $time_arr[0] = 0;
            }

            if (empty($time_arr[1])) {
                $time_arr[1] = 0;
            }

            foreach ($time_arr as $k => $v) {
                if (is_string($v) && !ctype_digit($v)) {
                    throw new InvalidArgumentException(
                        sprintf('"%s" is not a properly formatted time value.', $time),
                    );
                }

                $time_arr[$k] = (int) $v;
            }

            if ($time_arr[1] >= 60) {
                throw new InvalidArgumentException(
                    sprintf('"%s" is not a properly formatted time value.', $time),
                );
            }

            return round($time_arr[0] + ($time_arr[1] / 60), 2);
        default:
            throw new InvalidArgumentException(
                sprintf('"%s" is not a properly formatted time value.', $time),
            );
    }
}

/**
 * Convert time to number of seconds.
 *
 * @param  string $time
 * @return int
 */
function time_to_int($time)
{
    if (strpos($time, ':') !== false) {
        $time_arr = explode(':', $time);
        foreach ($time_arr as $k => $v) {
            $time_arr[$k] = (int) trim($v);
        }

        if (isset($time_arr[1]) && $time_arr[1] > 59) {
            $time_arr[1] = 59;
        }

        if (isset($time_arr[2]) && $time_arr[2] > 59) {
            $time_arr[2] = 59;
        }

        if (count($time_arr) == 2) {
            return $time_arr[0] * 3600 + $time_arr[1] * 60;
        } else {
            return $time_arr[0] * 3600 + $time_arr[1] * 60 + (int) $time_arr[2];
        }
    } elseif (strpos($time, ',') !== false || strpos($time, '.') !== false) {
        $time = str_replace(',', '.', $time);

        return floor((float) $time * 3600);
    } else {
        return ((int) $time) * 3600;
    }
}

/**
 * Convert decimal time value to (int) minutes.
 *
 * @param $new_time
 * @return int
 */
function time_to_minutes($new_time)
{
    $new_time = explode(':', float_to_time((float) $new_time));
    $current_time = ($new_time[0] * 60) + $new_time[1];

    return (int) $current_time;
}

/**
 * Convert (int) minutes to (float) time.
 *
 * @param        $sum_value
 * @return float
 */
function minutes_to_time($sum_value)
{
    $hours = 0;
    $minutes = $sum_value % 60;
    if ($sum_value >= 60) {
        $hours = floor($sum_value / 60);
    }

    return time_to_float($hours . ':' . $minutes);
}

/**
 * Convert Float Value to Time.
 *
 * @param  float  $time
 * @return string
 */
function float_to_time($time)
{
    if (is_float($time)) {
        $time_dec = $time - floor($time);

        $hours = floor($time);
        $minutes = round($time_dec * 60);

        return $hours . ':' . ($minutes < 10 ? "0{$minutes}" : $minutes);
    } elseif (is_int($time)) {
        return $time . ':00';
    } else {
        return $time;
    }
}

/**
 * Does the $text have CJK charactes.
 *
 * @param  string $text
 * @return bool
 */
function has_cjk_characters($text)
{
    return $text && preg_match("/\p{Han}+/u", $text);
}
