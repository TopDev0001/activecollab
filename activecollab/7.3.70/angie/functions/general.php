<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * General purpose functions.
 *
 * This file contains various general purpose functions used for string and
 * array manipulation, input filtering, ouput cleaning end so on.
 *
 * @package angie.functions
 */

/**
 * Round number to up value
 * 12.234 => 12.24
 * 12.236 => 12.24.
 *
 * @param        $value
 * @param  int   $decimals
 * @return mixed
 */
function round_up($value, $decimals = 2)
{
    $exp = pow(10, $decimals);

    return ceil($value * $exp) / $exp;
}

/**
 * Return begining of the string.
 *
 * @param  string $string
 * @param  int    $lenght
 * @param  string $etc
 * @param  bool   $striptags
 * @return string
 */
function str_excerpt($string, $lenght = 100, $etc = '...', $striptags = false)
{
    if ($striptags) {
        $string = strip_tags($string);
    }

    return trim(strlen_utf($string) <= $lenght + 3 ? $string : substr_utf($string, 0, $lenght) . $etc);
}

/**
 * Parse encoded string and return array of parameters.
 *
 * @param  string $str
 * @return array
 */
function parse_string($str)
{
    $result = null;
    parse_str($str, $result);

    return $result;
}

/**
 * convert backslashes to slashes.
 *
 * @param  string $path
 * @return string
 */
function fix_slashes($path)
{
    return str_replace('\\', '/', $path);
}

/**
 * Return path with trailing slash.
 *
 * @param  string $path Input path
 * @return string Path with trailing slash
 */
function with_slash($path)
{
    return str_ends_with($path, '/') ? $path : $path . '/';
}

/**
 * Remove trailing slash from the end of the path (if exists).
 *
 * @param  string $path File path that need to be handled
 * @return string
 */
function without_slash($path)
{
    return str_ends_with($path, '/') ? substr($path, 0, strlen($path) - 1) : $path;
}

/**
 * Make random string.
 *
 * @param  int    $length
 * @param  string $allowed_chars
 * @return string
 */
function make_string($length = 10, $allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
{
    $allowed_chars_len = strlen($allowed_chars);

    if ($allowed_chars_len == 1) {
        return str_pad('', $length, $allowed_chars);
    } else {
        $result = '';

        for ($i = 0; $i < $length; ++$i) {
            $result .= substr($allowed_chars, rand(0, $allowed_chars_len), 1);
        }

        while (strlen($result) < $length) {
            $result .= substr($allowed_chars, rand(0, $allowed_chars_len), 1);
        }

        return $result;
    }
}

// ---------------------------------------------------
//  Input validation
// ---------------------------------------------------

/**
 * Check if selected email has valid email format.
 *
 * @param  string $user_email Email address
 * @return bool
 */
function is_valid_email($user_email)
{
    if (function_exists('filter_var')) {
        return (bool) filter_var($user_email, FILTER_VALIDATE_EMAIL);
    } else {
        if (strstr($user_email, '@') && strstr($user_email, '.')) {
            return (bool) preg_match('/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}$/i', $user_email);
        }
    }

    return false;
}

/**
 * Split email on name and address part.
 *
 * @param $str
 * @return array
 */
function email_split($str)
{
    $name = '';
    $email = '';

    if (substr($str, 0, 1) == '<') {
        // first character = <
        $email = str_replace(['<', '>'], '', $str);
    } else {
        if (strpos($str, ' <') !== false) {
            // possibly = name <email>
            [$name, $email] = explode(' <', $str);
            $email = str_replace('>', '', $email);
            if (!is_valid_email($email)) {
                $email = '';
            }
            $name = str_replace(['"', "'"], '', $name);
        } else {
            if (is_valid_email($str)) {
                // just the email
                $email = $str;
            } else {
                // unknown
                $name = $str;
            }
        }
    }

    return [trim($name), trim($email)];
}

/**
 * Verify the syntax of the given URL.
 *
 * - samples
 *    http://127.0.0.1 : valid
 *    http://pero_mara.google.com : valid
 *    http://pero-mara.google.com : valid
 *    https://pero-mara.goo-gle.com/something : valid
 *    http://pero-mara.goo_gle.com/~we_use : valid
 *    http://www.google.com : valid
 *    http://activecollab.dev : valid
 *    http://127.0.0.1/~something : valid
 *    http://127.0.0.1/something : valid
 *    http://333.0.0.1 : invalid
 *    http://dev : invalid
 *    .dev : invalid
 *    activecollab.dev : invalid
 *    http://something : invalid
 *    http://127.0 : invalid
 *
 * @param string $url The URL to verify
 */
function is_valid_url(string $url): bool
{
    return str_starts_with(strtolower($url), 'http://localhost') || filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * verify that given string is valid ip address.
 *
 * @param  string $ip_address
 * @return bool
 */
function is_valid_ip_address($ip_address)
{
    if (preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $ip_address)) {
        return true;
    }

    return false;
}

/**
 * This function will return true if $str is valid function name (made out of
 * alpha numeric characters + underscore).
 *
 * @param  string $str
 * @return bool
 */
function is_valid_function_name($str)
{
    $check_str = trim($str);
    if ($check_str == '') {
        return false; // empty string
    }

    $first_char = substr_utf($check_str, 0, 1);
    if (is_numeric($first_char)) {
        return false; // first char can't be number
    }

    return (bool) preg_match('/^([a-zA-Z0-9_]*)$/', $check_str);
}

/**
 * Check if specific string is valid hash. Lenght is not checked!
 *
 * @param  string $hash
 * @return bool
 */
function is_valid_hash($hash)
{
    return (bool) preg_match('/^([a-f0-9]*)$/', $hash);
}

/**
 * Validate CC number.
 *
 * @param $credit_card_number
 * @return mixed
 */
function is_valid_cc($credit_card_number)
{
    $firstnumber = substr($credit_card_number, 0, 1);

    switch ($firstnumber) {
        case 3:
            if (!preg_match('/^3\d{3}[ \-]?\d{6}[ \-]?\d{5}$/', $credit_card_number)) {
                return false;
            }
            break;
        case 4:
            if (!preg_match('/^4\d{3}[ \-]?\d{4}[ \-]?\d{4}[ \-]?\d{4}$/', $credit_card_number)) {
                return false;
            }
            break;
        case 5:
            if (!preg_match('/^5\d{3}[ \-]?\d{4}[ \-]?\d{4}[ \-]?\d{4}$/', $credit_card_number)) {
                return false;
            }
            break;
        case 6:
            if (!preg_match('/^6011[ \-]?\d{4}[ \-]?\d{4}[ \-]?\d{4}$/', $credit_card_number)) {
                return false;
            }
            break;
        default:
            return false;
    }

    // Here's where we use the Luhn Algorithm
    $credit_card_number = str_replace('-', '', $credit_card_number);
    $map = [
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
        0, 2, 4, 6, 8, 1, 3, 5, 7, 9,
    ];
    $sum = 0;
    $last = strlen($credit_card_number) - 1;

    for ($i = 0; $i <= $last; ++$i) {
        $sum += $map[$credit_card_number[$last - $i] + ($i & 1) * 10];
    }

    if ($sum % 10 != 0) {
        return false;
    }

    return true;
}

/**
 * @param  string $card_number
 * @param  string $security_code
 * @return bool
 */
function is_valid_cvv($card_number, $security_code)
{
    // Get the first number of the credit card so we know how many digits to look for
    $firstnumber = (int) substr($card_number, 0, 1);

    if ($firstnumber === 3) {
        if (!preg_match("/^\d{4}$/", $security_code)) {
            return false; // The credit card is an American Express card but does not have a four digit CVV code
        }
    } else {
        if (!preg_match("/^\d{3}$/", $security_code)) {
            return false; // The credit card is a Visa, MasterCard, or Discover Card card but does not have a three digit CVV code
        }
    }

    return true;
}

// ---------------------------------------------------
//  Cleaning
// ---------------------------------------------------

/**
 * This function will return clean variable info.
 *
 * @param  mixed  $var
 * @param  string $indent              Indent is used when dumping arrays recursivly
 * @param  string $indent_close_bracet Indent close bracket param is used
 *                                     internaly for array output. It is shorter that var indent for 2 spaces
 * @return mixed
 */
function clean_var_info($var, $indent = '&nbsp;&nbsp;', $indent_close_bracet = '')
{
    if (is_object($var)) {
        return 'Object (class: ' . get_class($var) . ')';
    } elseif (is_resource($var)) {
        return 'Resource (type: ' . get_resource_type($var) . ')';
    } elseif (is_array($var)) {
        $result = 'Array (';
        if (count($var)) {
            foreach ($var as $k => $v) {
                $k_for_display = is_int($k) ? $k : "'" . clean($k) . "'";
                $result .= "\n" . $indent . '[' . $k_for_display . '] => ' . clean_var_info($v, $indent . '&nbsp;&nbsp;', $indent_close_bracet . $indent);
            }
        }

        return $result . "\n$indent_close_bracet)";
    } elseif (is_int($var)) {
        return '(int)' . $var;
    } elseif (is_float($var)) {
        return '(float)' . $var;
    } elseif (is_bool($var)) {
        return $var ? 'true' : 'false';
    } elseif (is_null($var)) {
        return 'NULL';
    } else {
        return "(string) '" . clean($var) . "'";
    }
}

/**
 * Equivalent to htmlspecialchars(), but allows &#[0-9]+ (for unicode).
 *
 * @param  string            $str
 * @return string
 * @throws InvalidParamError
 */
function clean($str)
{
    if (is_scalar($str)) {
        $str = preg_replace('/&(?!#(?:[0-9]+|x[0-9A-F]+);?)/si', '&amp;', $str);
        $str = str_replace(['<', '>', '"'], ['&lt;', '&gt;', '&quot;'], $str);

        return $str;
    } elseif ($str === null) {
        return '';
    } else {
        throw new InvalidParamError('str', $str, '$str needs to be scalar value');
    }
}

/**
 * Convert entities back to valid characteds.
 *
 * @param  string $escaped_string
 * @return string
 */
function undo_htmlspecialchars($escaped_string)
{
    $search = ['&amp;', '&lt;', '&gt;', '&quot;'];
    $replace = ['&', '<', '>', '"'];

    return str_replace($search, $replace, $escaped_string);
}

// ---------------------------------------------------
//  Array handling functions
// ---------------------------------------------------

/**
 * Check to see are two array equal.
 *
 * @param $arr1 array
 * @param $arr2 array
 * @return bool
 */
function array_equal($arr1, $arr2)
{
    return !array_diff($arr1, $arr2) && !array_diff($arr2, $arr1);
}

/**
 * Is $var foreachable.
 *
 * This function will return true if $var is array or if it can be iterated
 * over and it is not empty
 *
 * @param  mixed $var
 * @return bool
 */
function is_foreachable($var)
{
    return !empty($var) && (is_array($var) || $var instanceof IteratorAggregate);
}

/**
 * Get stdin output.
 *
 * @return string
 */
function get_stdin()
{
    $content = '';

    // open stdin
    $stdin = fopen('php://stdin', 'r');

    // loop and get content
    while (!feof($stdin)) {
        $content .= fgets($stdin, 4096);
    }

    // close stdin
    fclose($stdin);

    return $content;
}

/**
 * Returns true if $var is array of ID-s (numeric values).
 *
 * @param  mixed $var
 * @return bool
 */
function is_array_of_ids($var)
{
    if (is_array($var)) {
        foreach ($var as $v) {
            if (!is_numeric($v)) {
                return false;
            }
        }

        return true;
    }

    return false;
}

/**
 * Returns true if $var is array of instance of a given calss.
 *
 * @param  mixed  $var
 * @param  string $of_class
 * @return bool
 */
function is_array_of_instances($var, $of_class)
{
    if (is_array($var)) {
        foreach ($var as $v) {
            if (!($v instanceof $of_class)) {
                return false;
            }
        }

        return true;
    }

    return false;
}

/**
 * Return variable from an array.
 *
 * If field $name does not exists in array this function will return $default
 *
 * @param  array  $from      Hash
 * @param  string $name
 * @param  mixed  $default
 * @param  bool   $and_unset
 * @return mixed
 */
function array_var(&$from, $name, $default = null, $and_unset = false)
{
    if (is_array($from) || (is_object($from) && $from instanceof ArrayAccess)) {
        if ($and_unset) {
            if (array_key_exists($name, $from)) {
                $result = $from[$name];
                unset($from[$name]);

                return $result;
            }
        } else {
            return array_key_exists($name, $from) ? $from[$name] : $default;
        }
    }

    return $default;
}

/**
 * Return required value from array.
 *
 * @param  array                $from
 * @param  string               $name
 * @param  bool                 $and_unset
 * @param  string               $instance_of
 * @return mixed
 * @throws InvalidParamError
 * @throws InvalidInstanceError
 */
function array_required_var(&$from, $name, $and_unset = false, $instance_of = null)
{
    if ((is_array($from) || (is_object($from) && $from instanceof ArrayAccess)) && array_key_exists($name, $from)) {
        if ($instance_of !== null && !($from[$name] instanceof $instance_of)) {
            throw new InvalidInstanceError($name, $from[$name], $instance_of);
        }

        if ($and_unset) {
            $result = $from[$name];
            unset($from[$name]);

            return $result;
        } else {
            return $from[$name];
        }
    }

    throw new InvalidParamError('name', $name, "'$name' not found in array");
}

/**
 * Convert an array to a single CSV row.
 *
 * @param  array  $value_set
 * @return string
 */
function array_to_csv_row($value_set)
{
    $values = [];
    $separator = defined('DEFAULT_CSV_SEPARATOR') ? DEFAULT_CSV_SEPARATOR : ',';

    foreach ($value_set as $value) {
        if (!is_scalar($value)) {
            AngieApplication::log()->error('Value sent to CSV encoder is not a scalar', [
                'value' => $value,
                'full_row' => $value_set,
            ]);
        }

        $value = str_replace('"', '""', $value);

        if (strpos($value, $separator) !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false || strpos($value, "\r") !== false) {
            $value = str_replace(["\r\n", "\r", "\n"], [' ', ' ', ' '], $value);
        }

        $values[] = "'{$value}'";
    }

    return implode($separator, $values) . "\n";
}

/**
 * Returns first element of an array.
 *
 * If $key is true first key will be returned, value otherwise.
 *
 * @param  array|IteratorAggregate $arr
 * @param  bool                    $key
 * @return mixed
 */
function first($arr, $key = false)
{
    foreach ($arr as $k => $v) {
        return $key ? $k : $v;
    }

    return null;
}

// ---------------------------------------------------
//  Converters
// ---------------------------------------------------

/**
 * Cast row data to date value (object of DateValue class).
 *
 * @param  mixed     $value
 * @return DateValue
 */
function dateval($value)
{
    if (empty($value)) {
        return null;
    }

    if ($value instanceof DateValue) {
        return $value;
    } elseif (is_int($value) || is_string($value)) {
        return new DateValue($value);
    } else {
        return null;
    }
}

/**
 * Cast raw datetime format (string) to DateTimeValue object.
 *
 * @param  string        $value
 * @return DateTimeValue
 */
function datetimeval($value)
{
    if (empty($value)) {
        return null;
    }

    if ($value instanceof DateTimeValue) {
        return $value;
    } elseif ($value instanceof DateValue) {
        return new DateTimeValue($value->toMySQL());
    } elseif (is_int($value) || is_string($value)) {
        return new DateTimeValue($value);
    } else {
        return null;
    }
}

/**
 * Cast raw datetime format (string) to DateTimeValue object.
 *
 * @param  string $value
 * @return string
 */
function timeval($value)
{
    if (empty($value)) {
        return null;
    }

    return (string) $value;
}

/**
 * Conver money string to float (culture aware).
 *
 * @param  mixed $value
 * @return float
 */
function moneyval($value)
{
    if (is_float($value) || is_int($value)) {
        return $value;
    } else {
        $point_pos = strrpos($value, '.');
        $comma_pos = strrpos($value, ',');

        if ($point_pos !== false && $comma_pos !== false) {
            if ($point_pos > $comma_pos) {
                return (float) str_replace(',', '', $value);
            } else {
                $result = '';
                $value = str_replace(',', '.', $value);

                for ($i = 0; $i < strlen($value); ++$i) {
                    if ($value[$i] == '.' && $i != $comma_pos) {
                        continue;
                    }

                    $result .= $value[$i];
                }

                return (float) $result;
            }
        } elseif ($comma_pos) {
            return (float) str_replace(',', '.', $value);
        } else {
            return (float) $value;
        }
    }
}

// ---------------------------------------------------
//  Misc functions
// ---------------------------------------------------

/**
 * Return max upload size.
 *
 * This function will check for max upload size and return value in bytes. By default it will compare values of
 * upload_max_filesize and post_max_size from php.ini, but it can also take additional values provided as arguments
 * (for instance, if you store data in MySQL database one of the limiting factors can be max_allowed_packet
 * configuration value).
 *
 * Examples:
 * <pre>
 * $max_size = get_max_upload_size(); // check only data from php.ini
 * $max_size = get_max_upload_size(12000, 18000); // take this values into calculation too
 * </pre>
 *
 * @param mixed
 * @return int
 */
function get_max_upload_size()
{
    static $size = false;

    if ($size === false) {
        $size = php_config_value_to_bytes(ini_get('upload_max_filesize')); // Start with upload max size

        $post_max_size = php_config_value_to_bytes(ini_get('post_max_size'));

        if ($size > $post_max_size) {
            $size = $post_max_size;
        }
    }

    return $size;
}

/**
 * Convert filesize value from php.ini to bytes.
 *
 * Convert PHP config value (2M, 8M, 200K...) to bytes. This function was taken from PHP documentation. $val is string
 * value that need to be converted
 *
 * @param  string $val
 * @return int
 */
function php_config_value_to_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);

    if (!ctype_digit($last)) {
        $val = substr($val, 0, strlen($val) - 1);
    }

    if ($last === 'g') {
        $val *= 1024 * 1024 * 1024;
    } elseif ($last === 'm') {
        $val *= 1024 * 1024;
    } elseif ($last === 'k') {
        $val *= 1024;
    }

    return (int) floor((float) $val);
}

// ---------------------------------------------------
//  Image management
// ---------------------------------------------------

/**
 * Check if we have valid image for manipulation.
 *
 * @param  string $path  - path to the image
 * @param  bool   $throw - if true, function will throw errors
 * @return bool
 */
function check_image($path, $throw = true)
{
    if (!is_file($path)) {
        if ($throw) {
            throw new RuntimeException(sprintf('File "%s" does not exist.', $path));
        } else {
            return false;
        }
    }

    [$max_width, $max_height] = explode('x', strtolower(IMAGE_SIZE_CONSTRAINT));
    if ($max_width && $max_height) {
        [$image_width, $image_height] = getimagesize($path);
        if (!$image_width || !$image_height) {
            if ($throw) {
                throw new RuntimeException('Image could not be loaded. Check if file you are uploading is corrupted');
            } else {
                return false;
            }
        }

        // switch dimensions
        if ($image_height > $image_width) {
            $temp = $image_width;
            $image_width = $image_height;
            $image_height = $temp;
        }

        if ($image_width > $max_width || $image_height > $max_height) {
            if ($throw) {
                throw new RuntimeException(sprintf('Uploaded image is too large. Maximum size of image is %s pixels', IMAGE_SIZE_CONSTRAINT));
            } else {
                return false;
            }
        }
    }

    // if file size is not right
    if (filesize($path) > RESIZE_SMALLER_THAN) {
        if ($throw) {
            throw new RuntimeException(sprintf('File size of image you uploaded is too large. Max file size is %s', format_file_size(RESIZE_SMALLER_THAN)));
        } else {
            return false;
        }
    }

    return true;
}

/**
 * Open image file.
 *
 * This function will try to open image file
 *
 * @param  string          $file
 * @return array|bool|null
 */
function open_image($file)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $info = getimagesize($file);
    if ($info) {
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return [
                    'type' => IMAGETYPE_JPEG,
                    'resource' => imagecreatefromjpeg($file),
                ];
            case IMAGETYPE_GIF:
                return [
                    'type' => IMAGETYPE_GIF,
                    'resource' => imagecreatefromgif($file),
                ];
            case IMAGETYPE_PNG:
                return [
                    'type' => IMAGETYPE_PNG,
                    'resource' => imagecreatefrompng($file),
                ];
            case IMAGETYPE_BMP:
                return [
                    'type' => IMAGETYPE_BMP,
                    'resource' => imagecreatefrombmp($file),
                ];
        }
    }

    return null;
}

if (!function_exists('imagecreatefrombmp')) {
    /**
     * Create a new BMP image from file or URL.
     *
     * @param  string        $filename - path to the bmp file
     * @return bool|resource - resource an image resource identifier on success, false on errors
     */
    function imagecreatefrombmp($filename)
    {
        if (!$f1 = fopen($filename, 'rb')) {
            return false;
        }

        $FILE = unpack('vfile_type/Vfile_size/Vreserved/Vbitmap_offset', fread($f1, 14));
        if ($FILE['file_type'] != 19778) {
            return false;
        }

        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
            '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
            '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0) {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4) {
            $BMP['decal'] = 0;
        }

        $PALETTE = [];
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }

        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if ($BMP['bits_per_pixel'] == 24) {
                    $COLOR = unpack('V', substr($IMG, $P, 3) . $VIDE);
                } elseif ($BMP['bits_per_pixel'] == 16) {
                    $COLOR = unpack('n', substr($IMG, $P, 2));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 8) {
                    $COLOR = unpack('n', $VIDE . substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 4) {
                    $COLOR = unpack('n', $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 2) % 2 == 0) {
                        $COLOR[1] = ($COLOR[1] >> 4);
                    } else {
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 1) {
                    $COLOR = unpack('n', $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 8) % 8 == 0) {
                        $COLOR[1] = $COLOR[1] >> 7;
                    } elseif (($P * 8) % 8 == 1) {
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    } elseif (($P * 8) % 8 == 2) {
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    } elseif (($P * 8) % 8 == 3) {
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    } elseif (($P * 8) % 8 == 4) {
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    } elseif (($P * 8) % 8 == 5) {
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    } elseif (($P * 8) % 8 == 6) {
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    } elseif (($P * 8) % 8 == 7) {
                        $COLOR[1] = ($COLOR[1] & 0x1);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } else {
                    return false;
                }
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                ++$X;
                $P += $BMP['bytes_per_pixel'];
            }
            --$Y;
            $P += $BMP['decal'];
        }

        fclose($f1);

        return $res;
    }
}

/**
 * This function will save image resource into desired file.
 *
 * @param  mixed  $image
 * @param  string $filename
 * @param  string $type
 * @param  int    $quality
 * @param  bool   $close_after_saving
 * @return bool
 */
function save_image($image, $filename, $type, $quality = 80, $close_after_saving = true)
{
    if ($type === IMAGETYPE_GIF && !function_exists('imagegif')) {
        return false;
    }

    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($image, $filename, $quality);
            break;

        case IMAGETYPE_GIF:
            $result = imagegif($image, $filename);
            break;

        case IMAGETYPE_PNG:
            $result = imagepng($image, $filename);
            break;
        default:
            $result = false;
    }

    if ($close_after_saving) {
        imagedestroy($image);
    }

    return $result;
}

/**
 * Resize input image to fit given constraints.
 *
 * @param  mixed  $input
 * @param  string $dest_file
 * @param  int    $max_width
 * @param  int    $max_height
 * @param  string $output_type
 * @param  int    $quality
 * @param  bool   $enlarge
 * @return bool
 */
function scale_image($input, $dest_file, $max_width, $max_height, $output_type = null, $quality = 80, $enlarge = false)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    if (is_array($input) && array_key_exists('type', $input) && array_key_exists('resource', $input)) {
        $open_image = $input;
        $close_resource = false;
    } else {
        $open_image = open_image($input);
        $close_resource = true;
        if (!is_array($open_image)) {
            throw new RuntimeException('Could not parse image: ' . $input);
        }
    }

    $image_type = $open_image['type'];
    $image = $open_image['resource'];

    if ($output_type === null) {
        $output_type = $image_type;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    $scale = min($max_width / $width, $max_height / $height);

    if ($scale <= 1) {
        $new_width = floor($scale * $width);
        $new_height = floor($scale * $height);

        $resulting_image = imagecreatetruecolor($new_width, $new_height);
        if (can_use_image_alpha($output_type)) {
            imagealphablending($resulting_image, false);
            imagesavealpha($resulting_image, true);
            $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
            imagefilledrectangle($resulting_image, 0, 0, $new_width, $new_height, $alpha);
        } else {
            $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
            imagefill($resulting_image, 0, 0, $white_color);
        }

        imagecopyresampled($resulting_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    } else {
        $resulting_image = imagecreatetruecolor($max_width, $max_height);

        if (can_use_image_alpha($output_type)) {
            imagealphablending($resulting_image, false);
            imagesavealpha($resulting_image, true);
            $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
            imagefilledrectangle($resulting_image, 0, 0, $max_width, $max_height, $alpha);
        } else {
            $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
            imagefill($resulting_image, 0, 0, $white_color);
        }

        if ($enlarge) {
            $new_width = floor($width * $scale);
            $new_height = floor($height * $scale);
            imagecopyresampled($resulting_image, $image, round(($max_width - $new_width) / 2), round(($max_height - $new_height) / 2), 0, 0, $new_width, $new_height, $width, $height);
        } else {
            imagecopy($resulting_image, $image, round(($max_width - $width) / 2), round(($max_height - $height) / 2), 0, 0, $width, $height);
        }
    }

    if ($close_resource) {
        imagedestroy($image);
    }

    return save_image($resulting_image, $dest_file, $output_type, $quality);
}

/**
 * Scales image to fit specified dimensions.
 *
 * @param  mixed  $input
 * @param  string $dest_file
 * @param  int    $width
 * @param  int    $height
 * @param  int    $output_type
 * @param  int    $quality
 * @return bool
 */
function scale_and_fit_image($input, $dest_file, $width, $height, $output_type = null, $quality = 80)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    if (is_array($input) && array_key_exists('type', $input) && array_key_exists('resource', $input)) {
        $open_image = $input;
        $close_resource = false;
    } else {
        $open_image = open_image($input);
        $close_resource = true;
        if (!is_array($open_image)) {
            throw new RuntimeException('Could not parse image: ' . $input);
        }
    }

    $image_type = $open_image['type'];
    $image = $open_image['resource'];

    if ($output_type === null) {
        $output_type = $image_type;
    }

    $src_width = imagesx($image);
    $src_height = imagesy($image);

    $scale = min($width / $src_width, $height / $src_height);

    if ($scale < 1) {
        $destination_width = floor($src_width * $scale);
        $destination_height = floor($src_height * $scale);
    } else {
        $destination_width = $src_width;
        $destination_height = $src_height;
    }

    $destination_x_offset = 0;
    $destination_y_offset = 0;

    $resulting_image = imagecreatetruecolor($destination_width, $destination_height);
    if (can_use_image_alpha($output_type)) {
        imagealphablending($resulting_image, false);
        imagesavealpha($resulting_image, true);
        $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
        imagefilledrectangle($resulting_image, 0, 0, $width, $height, $alpha);
    } else {
        $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
        imagefill($resulting_image, 0, 0, $white_color);
    }

    imagecopyresampled($resulting_image, $image, $destination_x_offset, $destination_y_offset, 0, 0, $destination_width, $destination_height, $src_width, $src_height);

    if ($close_resource) {
        imagedestroy($image);
    }

    return save_image($resulting_image, $dest_file, $output_type, $quality);
}

/**
 * Scales image to fit specified dimensions.
 *
 * @param  mixed  $input
 * @param  string $dest_file
 * @param  int    $width
 * @param  int    $height
 * @param  int    $output_type
 * @param  int    $quality
 * @return bool
 */
function scale_image_and_force_size($input, $dest_file, $width, $height, $output_type = null, $quality = 80)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    if (is_array($input) && array_key_exists('type', $input) && array_key_exists('resource', $input)) {
        $open_image = $input;
        $close_resource = false;
    } else {
        $open_image = open_image($input);
        $close_resource = true;
        if (!is_array($open_image)) {
            throw new RuntimeException('Could not parse image: ' . $input);
        }
    }

    $image_type = $open_image['type'];
    $image = $open_image['resource'];

    if ($output_type === null) {
        $output_type = $image_type;
    }

    $src_width = imagesx($image);
    $src_height = imagesy($image);

    $scale = min($width / $src_width, $height / $src_height);

    if ($scale < 1) {
        $destination_clip_width = floor($src_width * $scale);
        $destination_clip_height = floor($src_height * $scale);
    } else {
        $destination_clip_width = $src_width;
        $destination_clip_height = $src_height;
    }

    if ($destination_clip_width < $width) {
        $destination_x_offset = floor(($width - $destination_clip_width) / 2);
    } else {
        $destination_x_offset = 0;
    }

    if ($destination_clip_height < $height) {
        $destination_y_offset = floor(($height - $destination_clip_height) / 2);
    } else {
        $destination_y_offset = 0;
    }

    $resulting_image = imagecreatetruecolor($width, $height);
    if (can_use_image_alpha($output_type)) {
        imagealphablending($resulting_image, false);
        imagesavealpha($resulting_image, true);
        $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
        imagefilledrectangle($resulting_image, 0, 0, $width, $height, $alpha);
    } else {
        $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
        imagefill($resulting_image, 0, 0, $white_color);
    }

    imagecopyresampled($resulting_image, $image, $destination_x_offset, $destination_y_offset, 0, 0, $destination_clip_width, $destination_clip_height, $src_width, $src_height);

    if ($close_resource) {
        imagedestroy($image);
    }

    return save_image($resulting_image, $dest_file, $output_type, $quality);
}

/**
 * Resize image, and crop it so you get squared image (best for thumbnails).
 *
 * if $input_file is smaller than $dimension, resulting image will still have square shape and $dimension, and
 * $input_file will be stretched to $dimension
 *
 * @param  mixed  $input
 * @param  string $dest_file
 * @param  int    $dimension
 * @param  int    $offset_x
 * @param  int    $offset_y
 * @param  int    $output_type
 * @param  int    $quality
 * @return bool
 */
function scale_and_crop_image($input, $dest_file, $dimension, $offset_x = null, $offset_y = null, $output_type = null, $quality = 80)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    if (is_array($input) && array_key_exists('type', $input) && array_key_exists('resource', $input)) {
        $open_image = $input;
        $close_resource = false;
    } else {
        $open_image = open_image($input);
        $close_resource = true;
        if (!is_array($open_image)) {
            throw new RuntimeException('Could not parse image: ' . $input);
        }
    }

    $image_type = $open_image['type'];
    $image = $open_image['resource'];

    if ($output_type === null) {
        $output_type = $image_type;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    $current_dimension = min($width, $height);

    if ($offset_x === null && $offset_y === null) {
        $offset_x = round(($width - $current_dimension) / 2);
        $offset_y = round(($height - $current_dimension) / 2);
    }

    $resulting_image = imagecreatetruecolor($dimension, $dimension);
    if (can_use_image_alpha($output_type)) {
        imagealphablending($resulting_image, false);
        imagesavealpha($resulting_image, true);
        $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
        imagefilledrectangle($resulting_image, 0, 0, $dimension, $dimension, $alpha);
    } else {
        $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
        imagefill($resulting_image, 0, 0, $white_color);
    }

    imagecopyresampled($resulting_image, $image, 0, 0, $offset_x, $offset_y, $dimension, $dimension, $current_dimension, $current_dimension);

    if ($close_resource) {
        imagedestroy($image);
    }

    return save_image($resulting_image, $dest_file, $output_type, $quality);
}

/**
 * Resize image, and crop it so you get squared image (best for thumbnails).
 *
 * if $input_file is smaller than $dimension, resulting image will still have square shape and $dimension, and
 * $input_file will be stretched to $dimension
 *
 * @param  mixed  $input
 * @param  string $dest_file
 * @param  int    $dest_width
 * @param  int    $dest_height
 * @param  int    $src_offset_x
 * @param  int    $src_offset_y
 * @param  int    $output_type
 * @param  int    $quality
 * @return bool
 */
function scale_and_crop_image_alt($input, $dest_file, $dest_width, $dest_height, $src_offset_x = null, $src_offset_y = null, $output_type = null, $quality = 80)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $dimension = max($dest_width, $dest_height);

    if (is_array($input) && array_key_exists('type', $input) && array_key_exists('resource', $input)) {
        $open_image = $input;
        $close_resource = false;
    } else {
        $open_image = open_image($input);
        $close_resource = true;
        if (!is_array($open_image)) {
            throw new RuntimeException('Could not parse image: ' . $input);
        }
    }

    $image_type = $open_image['type'];
    $image = $open_image['resource'];

    if ($output_type === null) {
        $output_type = $image_type;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    $current_dimension = min($width, $height);

    if ($src_offset_x === null && $src_offset_y === null) {
        $src_offset_x = round(($width - $current_dimension) / 2);
        $src_offset_y = round(($height - $current_dimension) / 2);
    }

    $src_width = $current_dimension;
    $src_height = $current_dimension;

    if ($dest_height > $dest_width) {
        $ratio = $dest_width / $dest_height;
        $new_src_width = floor($src_width * $ratio);
        $src_offset_x = $src_offset_x + floor(($src_width - $new_src_width) / 2);
        $src_width = $new_src_width;
    } elseif ($dest_width > $dest_height) {
        $ratio = $dest_height / $dest_width;
        $new_src_height = floor($src_height * $ratio);
        $src_offset_y = $src_offset_y + floor(($src_height - $new_src_height) / 2);
        $src_height = $new_src_height;
    }

    $resulting_image = imagecreatetruecolor($dest_width, $dest_height);
    if (can_use_image_alpha($output_type)) {
        imagealphablending($resulting_image, false);
        imagesavealpha($resulting_image, true);
        $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
        imagefilledrectangle($resulting_image, 0, 0, $dest_width, $dest_height, $alpha);
    } else {
        $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
        imagefill($resulting_image, 0, 0, $white_color);
    }

    imagecopyresampled($resulting_image, $image, 0, 0, $src_offset_x, $src_offset_y, $dest_width, $dest_height, $src_width, $src_height);

    if ($close_resource) {
        imagedestroy($image);
    }

    return save_image($resulting_image, $dest_file, $output_type, $quality);
}

/**
 * Convert image to desired $type.
 *
 * @param  mixed  $input
 * @param  string $dest_file
 * @param  string $type
 * @return bool
 */
function convert_image($input, $dest_file, $type)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    if (is_array($input) && array_key_exists('type', $input) && array_key_exists('resource', $input)) {
        $open_image = $input;
        $close_resource = false;
    } else {
        $open_image = open_image($input);
        $close_resource = true;
        if (!is_array($open_image)) {
            throw new RuntimeException('Could not parse image: ' . $input);
        }
    }

    $image = $open_image['resource'];

    $width = imagesx($image);
    $height = imagesy($image);

    $resulting_image = imagecreatetruecolor($width, $height);
    if (can_use_image_alpha($type)) {
        imagealphablending($resulting_image, false);
        imagesavealpha($resulting_image, true);
        $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
        imagefilledrectangle($resulting_image, 0, 0, $width, $height, $alpha);
    } else {
        $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
        imagefill($resulting_image, 0, 0, $white_color);
    }

    imagecopyresampled($resulting_image, $image, 0, 0, 0, 0, $width, $height, $width, $height);

    if ($close_resource) {
        imagedestroy($image);
    }

    return save_image($resulting_image, $dest_file, $type);
}

/**
 * Stretch image.
 *
 * @param  mixed  $input
 * @param  string $dest_file
 * @param  int    $new_width
 * @param  int    $new_height
 * @param  mixed  $output_type
 * @param  int    $quality
 * @return bool
 */
function stretch_image($input, $dest_file, $new_width, $new_height, $output_type = null, $quality = 80)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    if (is_array($input) && array_key_exists('type', $input) && array_key_exists('resource', $input)) {
        $open_image = $input;
        $close_resource = false;
    } else {
        $open_image = open_image($input);
        $close_resource = true;
        if (!is_array($open_image)) {
            throw new RuntimeException('Could not parse image: ' . $input);
        }
    }

    $image_type = $open_image['type'];
    $image = $open_image['resource'];

    if ($output_type === null) {
        $output_type = $image_type;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    $resulting_image = imagecreatetruecolor($new_width, $new_height);
    if (can_use_image_alpha($output_type)) {
        imagealphablending($resulting_image, false);
        imagesavealpha($resulting_image, true);
        $alpha = imagecolorallocatealpha($resulting_image, 255, 255, 255, 127);
        imagefilledrectangle($resulting_image, 0, 0, $new_width, $new_height, $alpha);
    } else {
        $white_color = imagecolorallocate($resulting_image, 255, 255, 255);
        imagefill($resulting_image, 0, 0, $white_color);
    }

    imagecopyresampled($resulting_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    if ($close_resource) {
        imagedestroy($image);
    }

    $result = save_image($image, $dest_file, $output_type, $quality);

    return $result;
}

/**
 * check if hex color code is valid.
 *
 * @param  string $color_code
 * @return bool
 */
function is_valid_hex_color($color_code)
{
    if (!$color_code) {
        return false;
    }

    if (strlen($color_code) != 7) {
        return false;
    }

    if (!preg_match('/^#[a-f0-9]{6}$/i', $color_code)) {
        return false;
    }

    return true;
}

/**
 * Returns true if php is able to resize images.
 *
 * @return bool
 */
function can_resize_images()
{
    return extension_loaded('gd');
}

/**
 * Whether image transformations can be done with preserving alpha or not.
 *
 * @param  int  $output_type
 * @return bool
 */
function can_use_image_alpha($output_type)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    if (!(function_exists('imagealphablending') && function_exists('imagesavealpha') && function_exists('imagecolorallocatealpha'))) {
        return false;
    }

    return $output_type == IMAGETYPE_PNG;
}

/**
 * Convert comma-separated values to array and trim values, also remove empty ones.
 *
 * @param  string $string
 * @return array
 */
function csv_to_array($string)
{
    // convert csv into array
    $array = (array) explode(',', $string);

    // trim all array elements
    $array = array_map('trim', $array);

    // remove empty array elements
    $array = array_filter($array);

    return $array;
}

/**
 * Generate avatar image with initials.
 *
 * @param  string $filename
 * @param  int    $size
 * @param  string $text
 * @return bool
 */
function generate_avatar_with_initials($filename, $size, $text)
{
    $font_file = ANGIE_PATH . '/frameworks/environment/resources/fonts/ClearSans-Medium.ttf';

    // we need uppercase font
    $text = strtoupper($text);

    // determine font size
    $font_size = round($size / 3);

    // color presets
    $color_presets = [
        [252, 185, 103],
        [241, 193, 170],
        [251, 150, 153],
        [204, 153, 184],
        [196, 168, 255],
        [151, 203, 255],
        [174, 230, 224],
        [143, 183, 117],
    ];

    $font_color = [0, 0, 0, 30];

    // get first letter of the hash
    $hash_first_letter = strtolower(md5($text)[0]);

    // get the random color id
    $background_color_id = ord($hash_first_letter) % (count($color_presets));

    // get the random background color
    $background_color = $color_presets[$background_color_id];

    if (function_exists('imagettftext') && function_exists('imagettfbbox')) {
        // create image resource
        $avatar_image = imagecreatetruecolor($size, $size);
        // fill the background
        imagefill($avatar_image, 0, 0, imagecolorallocate($avatar_image, $background_color[0], $background_color[1], $background_color[2]));
        // determine text box size
        $text_box = imagettfbbox($font_size, 0, $font_file, $text);
        // determine x offset of printed text
        $x_offset = round(($size - ($text_box[2] - $text_box[0])) / 2);
        // text baseline
        $y_offset = round($size / 2 + $font_size / 2) - 1;

        // print out the text
        imagettftext($avatar_image, $font_size, 0, $x_offset, $y_offset, imagecolorallocatealpha($avatar_image, $font_color[0], $font_color[1], $font_color[2], $font_color[3]), $font_file, $text);
    } else {
        $small_size = 35;
        // create image resource
        $small_image = imagecreatetruecolor($small_size, $small_size);
        // fill the background
        imagefill($small_image, 0, 0, imagecolorallocate($small_image, $background_color[0], $background_color[1], $background_color[2]));
        // built in font
        $built_in_font = 5;
        // determine x offset of printed text
        $x_offset = ($small_size - strlen($text) * imagefontwidth($built_in_font)) / 2 + 1;
        // determine y offset of printed text
        $y_offset = ($small_size - imagefontheight($built_in_font)) / 2 - 1;
        // print centered text
        imagestring($small_image, $built_in_font, $x_offset, $y_offset, $text, imagecolorallocatealpha($small_image, $font_color[0], $font_color[1], $font_color[2], $font_color[3]));
        // scale to desired dimensions
        $avatar_image = imagescale($small_image, $size, $size);
        // destroy the temporary image
        imagedestroy($small_image);
    }

    // save image to file
    return save_image($avatar_image, $filename, IMAGETYPE_PNG, 100);
}
