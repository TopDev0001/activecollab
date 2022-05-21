<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * General set of functions for file handling.
 *
 * @package angie.functions
 */

/**
 * Check if specific folder is writable.
 *
 * is_writable() function has problems on Windows because it does not really
 * checks for ACLs; it checks just the value of Read-Only property and that
 * is incorect on some Windows installations.
 *
 * This function will actually try to create (and delete) a test file in order
 * to check if folder is really writable
 *
 * @param  string $path
 * @return bool
 */
function folder_is_writable($path)
{
    if (!is_dir($path)) {
        return false;
    }

    do {
        $test_file = with_slash($path) . sha1(uniqid(rand(), true));
    } while (is_file($test_file));

    $put = @file_put_contents($test_file, 'test');
    if ($put === false) {
        return false;
    }

    @unlink($test_file);

    return true;
}

/**
 * Check if specific file is writable.
 *
 * This function will try to open target file for writing (just open it!) in order to
 * make sure that this file is really writable. There are some known problems with
 * is_writable() on Windows (see description of folder_is_writable() function for more
 * details).
 *
 * @see folder_is_writable() function
 * @param  string $path
 * @param  bool   $check_for_existance
 * @return bool
 */
function file_is_writable($path, $check_for_existance = true)
{
    if (is_file($path)) {
        $open = @fopen($path, 'a+');
        if ($open === false) {
            return false;
        }

        @fclose($open);

        return true;
    } else {
        if ($check_for_existance) {
            return false;
        } else {
            return folder_is_writable(dirname($path));
        }
    }
}

/**
 * Return the files a from specific directory.
 *
 * This function will walk through $dir and read all file names. Result can be filtered by file extension (accepted
 * param is single extension or array of extensions). If $recursive is set to true this function will walk recursivlly
 * through subfolders.
 *
 * Example:
 * <pre>
 * $files = get_files($dir, array('doc', 'pdf', 'xst'));
 * foreach ($files as $file_path) {
 *   print $file_path;
 * }
 * </pre>
 *
 * @param  string $dir
 * @param  mixed  $extension
 * @param  bool   $recursive
 * @return array
 */
function get_files($dir, $extension = null, $recursive = false)
{
    if (!is_dir($dir)) {
        return false;
    }

    $dir = with_slash($dir);
    if (!is_null($extension)) {
        if (is_array($extension)) {
            foreach ($extension as $k => $v) {
                $extension[$k] = strtolower($v);
            }
        } else {
            $extension = strtolower($extension);
        }
    }

    $d = dir($dir);
    $files = [];

    while (($entry = $d->read()) !== false) {
        if (str_starts_with($entry, '.')) {
            continue;
        }

        $path = $dir . $entry;

        if (is_file($path)) {
            if (is_null($extension)) {
                $files[] = $path;
            } else {
                if (is_array($extension)) {
                    if (in_array(strtolower(get_file_extension($path)), $extension)) {
                        $files[] = $path;
                    }
                } else {
                    if (strtolower(get_file_extension($path)) == $extension) {
                        $files[] = $path;
                    }
                }
            }
        } elseif (is_dir($path)) {
            if ($recursive) {
                $subfolder_files = get_files($path, $extension, true);
                if (is_array($subfolder_files)) {
                    $files = array_merge($files, $subfolder_files);
                }
            }
        }
    }

    $d->close();

    return count($files) > 0 ? $files : null;
}

/**
 * Return the folder list in provided directory folders are returned with
 * absolute path.
 *
 * This function ignores hidden folders!
 *
 * @param  string $dir
 * @param  bool   $recursive
 * @return array
 */
function get_folders($dir, $recursive = false)
{
    if (is_dir($dir)) {
        $folders = [];

        if ($dirstream = @opendir($dir)) {
            while (false !== ($filename = readdir($dirstream))) {
                $path = with_slash($dir) . $filename;
                if (substr($filename, 0, 1) != '.' && is_dir($path)) {
                    $folders[] = $path;
                    if ($recursive) {
                        $sub_folders = get_folders($path, $recursive);
                        if (is_array($sub_folders)) {
                            $folders = array_merge($folders, $sub_folders);
                        }
                    }
                }
            }
        }

        closedir($dirstream);

        return $folders;
    } else {
        return false;
    }
}

/**
 * Return file extension from specific filename. Examples:.
 *
 * get_file_extension('index.php') -> returns 'php'
 * get_file_extension('index.php', true) -> returns '.php'
 * get_file_extension('Blog.class.php', true) -> returns '.php'
 *
 * @param  string $path        File path
 * @param  bool   $leading_dot Include leading dot
 * @return string
 */
function get_file_extension($path, $leading_dot = false)
{
    $filename = basename($path);
    $dot_offset = (bool) $leading_dot ? 0 : 1;

    if (($pos = strrpos($filename, '.')) !== false) {
        return substr($filename, $pos + $dot_offset, strlen($filename));
    }

    return '';
}

/**
 * Get mime type.
 *
 * @param string $file
 * @param string $real_filename
 * @param bool   $use_native_functions - if false, mime type will be determined by file extension
 *
 * @return string
 */
function get_mime_type($file, $real_filename = null, $use_native_functions = true)
{
    if (function_exists('mime_content_type') && $use_native_functions) {
        $mime_type = trim(mime_content_type($file));
        if (!$mime_type) {
            return 'application/octet-stream';
        }
        $mime_type = explode(';', $mime_type);

        return $mime_type[0];
    } elseif (function_exists('finfo_open') && function_exists('finfo_file') && $use_native_functions) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file);
        finfo_close($finfo);

        return $mime_type;
    } else {
        if ($real_filename) {
            $file = $real_filename;
        }

        $mime_types = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        $extension = strtolower(get_file_extension($file));
        if (array_key_exists($extension, $mime_types)) {
            return $mime_types[$extension];
        } else {
            return 'application/octet-stream';
        }
    }
}

/**
 * Walks recursively through directory and calculates its total size.
 *
 * @param  string $dir                          Directory
 * @param  bool   $skip_files_starting_with_dot
 * @return int
 */
function dir_size($dir, $skip_files_starting_with_dot = true)
{
    $totalsize = 0;

    if ($dirstream = @opendir($dir)) {
        while (false !== ($filename = readdir($dirstream))) {
            $path = with_slash($dir) . $filename;

            if (is_link($path)) {
                continue;
            }

            if ($skip_files_starting_with_dot) {
                if (($filename != '.') && ($filename != '..') && ($filename[0] != '.')) {
                    if (is_file($path)) {
                        $totalsize += filesize($path);
                    }
                    if (is_dir($path)) {
                        $totalsize += dir_size($path, $skip_files_starting_with_dot);
                    }
                }
            } else {
                if (($filename != '.') && ($filename != '..')) {
                    if (is_file($path)) {
                        $totalsize += filesize($path);
                    }
                    if (is_dir($path)) {
                        $totalsize += dir_size($path, $skip_files_starting_with_dot);
                    }
                }
            }
        }
    }

    closedir($dirstream);

    return $totalsize;
}

/**
 * does the same as mkdir function on php5, except it's compatible with php4,
 * so folders are created recursive.
 *
 * @param  string $path
 * @param  int    $mode
 * @param  string $restriction_path
 * @return bool
 */
function recursive_mkdir($path, $mode = 0777, $restriction_path = '/')
{
    if (DIRECTORY_SEPARATOR == '/') {
        if (strpos($path, $restriction_path) !== 0) {
            return false;
        }
    } else {
        if (strpos(fix_slashes(strtolower($path)), fix_slashes(strtolower($restriction_path))) !== 0) {
            return false;
        }
    }

    $start_path = substr($path, 0, strlen($restriction_path));
    $allowed_path = substr($path, strlen($restriction_path));
    $original_path = $path;
    $path = fix_slashes($allowed_path);
    $dirs = explode('/', $path);
    $count = count($dirs);
    $path = '';
    for ($i = 0; $i < $count; ++$i) {
        if ($i == 0) {
            $path = $start_path;
        }
        if (DIRECTORY_SEPARATOR == '\\' && $path == '') {
            $path .= $dirs[$i];
        } else {
            $path .= '/' . $dirs[$i];
        }
        if (!is_dir($path)) {
            if (mkdir($path, $mode)) {
                if (DIRECTORY_SEPARATOR != '\\' && !chmod($path, $mode)) {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    return is_dir($original_path);
}

function safe_delete_dir($dir, $base_dir, bool $break_on_error = false): bool
{
    if (strpos($dir, $base_dir) === 0) {
        return delete_dir($dir, $break_on_error);
    }

    return false;
}
function delete_dir($dir, bool $break_on_error = false): bool
{
    if (!is_dir($dir)) {
        return false;
    }

    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if (($file != '.') && ($file != '..')) {
            $full_path = $dir . '/' . $file;

            if (is_dir($full_path)) {
                $dir_deleted = delete_dir($full_path);

                if ($break_on_error && !$dir_deleted) {
                    throw new RuntimeException(sprintf('Failed to delete directory %s', $full_path));
                }
            } elseif (is_file($full_path)) {
                $file_deleted = unlink($full_path);

                if ($break_on_error && !$file_deleted) {
                    throw new RuntimeException(sprintf('Failed to delete file %s', $full_path));
                }
            }
        }
    }

    closedir($dh);

    return (bool) rmdir($dir);
}

/**
 * Remove all files and folders from a given directory.
 *
 * @param  string $dir
 * @param  bool   $ignore_hidden_files
 * @return bool
 */
function empty_dir($dir, $ignore_hidden_files = false)
{
    if (is_dir($dir)) {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file == '.' || $file == '..' || ($ignore_hidden_files && substr($file, 0, 1) == '.')) {
                continue;
            }

            $fullpath = $dir . '/' . $file;

            if (is_dir($fullpath)) {
                delete_dir($fullpath);
            } else {
                unlink($fullpath);
            }
        }

        closedir($dh);

        return true;
    }

    return false;
}

/**
 * Format filesize.
 *
 * @param  string $value
 * @param  bool   $trim_zeros
 * @return string
 */
function format_file_size($value, $trim_zeros = true)
{
    $data = [
        'TB' => 1099511627776,
        'GB' => 1073741824,
        'MB' => 1048576,
        'kb' => 1024,
    ];

    // commented because of integer overflow on 32bit sistems
    // http://php.net/manual/en/language.types.integer.php#language.types.integer.overflow
    // $value = (integer) $value;
    foreach ($data as $unit => $bytes) {
        $in_unit = $value / $bytes;
        if ($in_unit > 0.9) {
            $formatted_number = number_format($in_unit, 2, NUMBER_FORMAT_DEC_SEPARATOR, NUMBER_FORMAT_THOUSANDS_SEPARATOR);

            if ($trim_zeros) {
                $formatted_number = trim(rtrim($formatted_number, '0'), NUMBER_FORMAT_DEC_SEPARATOR);
            }

            return $formatted_number . $unit;
        }
    }

    return (!empty($value) ? $value : 0) . 'b';
}
