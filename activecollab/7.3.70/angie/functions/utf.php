<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * MB string extension wrapper functions.
 *
 * This function will check if MB string extension is availalbe and use mb_
 * functions if it is. Otherwise it will use old PHP functions
 *
 * @package angie.functions
 */
define('CAN_USE_MBSTRING', extension_loaded('mbstring'));

/**
 * Extended substr function. If it finds mbstring extension it will use, else
 * it will use old substr() function.
 *
 * @param  string $string
 * @param  int    $start
 * @param  int    $length
 * @return string
 */
function substr_utf($string, $start = 0, $length = null)
{
    $start = (int) $start >= 0 ? (int) $start : 0;

    if (is_null($length)) {
        $length = strlen_utf($string) - $start;
    }

    return CAN_USE_MBSTRING ? mb_substr($string, $start, $length, 'UTF-8') : substr($string, $start, $length);
}

/**
 * UTF-8 safe str_replace.
 *
 * @param  string             $search
 * @param  string             $replace
 * @param  string             $subject
 * @param  int                $count
 * @return array|mixed|string
 */
function str_replace_utf($search, $replace, $subject, $count = null)
{
    return mb_str_replace($search, $replace, $subject, 'UTF-8', $count);
}

/**
 * mb_str_replace function found on php documentation page.
 *
 * @param               $search
 * @param               $replace
 * @param               $subject
 * @param  string|null  $encoding
 * @return array|string
 */
function mb_str_replace($search, $replace, $subject, $encoding = null, int &$count = null)
{
    if (empty($encoding)) {
        $encoding = mb_internal_encoding();
    }

    if (is_array($subject)) {
        $result = [];
        foreach ($subject as $item) {
            $result[] = mb_str_replace($search, $replace, $item, $encoding, $count);
        }

        return $result;
    }

    if (!is_array($search)) {
        return _mb_str_replace($search, $replace, $subject, $encoding, $count);
    }

    $replace_is_array = is_array($replace);
    foreach ($search as $key => $value) {
        $subject = _mb_str_replace(
            $value,
            $replace_is_array ? $replace[$key] : $replace,
            $subject,
            $encoding,
            $count
        );
    }

    return $subject;
}

/**
 * Implementation of mb_str_replace. Do not call directly. Enforces string parameters.
 */
function _mb_str_replace($search, $replace, $subject, $encoding = null, int &$count = null)
{
    $search_length = mb_strlen($search, $encoding);
    $subject_length = mb_strlen($subject, $encoding);
    $offset = 0;
    $result = '';

    while ($offset < $subject_length) {
        $match = mb_strpos($subject, $search, $offset, $encoding);
        if ($match === false) {
            if ($offset === 0) {
                // No match was ever found, just return the subject.
                return $subject;
            }
            // Append the final portion of the subject to the replaced.
            $result .= mb_substr($subject, $offset, $subject_length - $offset, $encoding);
            break;
        }
        if ($count !== null) {
            ++$count;
        }
        $result .= mb_substr($subject, $offset, $match - $offset, $encoding);
        $result .= $replace;
        $offset = $match + $search_length;
    }

    return $result;
}

/**
 * Return UTF safe string lenght.
 *
 * @param  string $string
 * @return int
 */
function strlen_utf($string)
{
    return CAN_USE_MBSTRING ? mb_strlen($string, 'UTF-8') : strlen($string);
}

/**
 * UTF safe strpos.
 *
 * @param  string $haystack
 * @param  string $needle
 * @param  int    $offset
 * @return mixed
 */
function strpos_utf($haystack, $needle, $offset = 0)
{
    return CAN_USE_MBSTRING ? mb_strpos($haystack, $needle, $offset, 'UTF-8') : strpos($haystack, $needle, $offset);
}

/**
 * UTF safe stripos.
 *
 * @param  string $haystack
 * @param  string $needle
 * @param  int    $offset
 * @return mixed
 */
function stripos_utf($haystack, $needle, $offset = 0)
{
    return CAN_USE_MBSTRING ? mb_stripos($haystack, $needle, $offset) : stripos($haystack, $needle, $offset);
}

/**
 * UTF friendly strtolower function.
 *
 * @param  string $string
 * @return string
 */
function strtolower_utf($string)
{
    return CAN_USE_MBSTRING ? mb_strtolower($string, 'UTF-8') : strtolower($string);
}

/**
 * Return uppercase string.
 *
 * @param  string $string
 * @return string
 */
function strtoupper_utf($string)
{
    return CAN_USE_MBSTRING ? mb_strtoupper($string, 'UTF-8') : strtoupper($string);
}

/**
 * Capitalize first letter, UTF-8 safe.
 *
 * @param  string $string
 * @return string
 */
function ucfirst_utf($string)
{
    if (trim($string) == '') {
        return $string; // Nothing to work with
    }

    if (CAN_USE_MBSTRING) {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    } else {
        return ucfirst($string);
    }
}

/**
 * Return number of $needle occurances in $haystack.
 *
 * @param  string $haystack
 * @param  string $needle
 * @return int
 */
function substr_count_utf($haystack, $needle)
{
    if (CAN_USE_MBSTRING) {
        return mb_substr_count($haystack, $needle);
    } else {
        return substr_count($haystack, $needle);
    }
}
