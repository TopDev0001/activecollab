<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

if (defined('ANGIE_INITED') && ANGIE_INITED) {
    return;
}

const ANGIE_INITED = true;

/**
 * Die with printed execution time.
 *
 * @TODO remove
 */
function angie_execution_time()
{
    exit(round(microtime(true) - ANGIE_SCRIPT_TIME, 5));
}

// Environment path is used by many environment classes. If not
// defined do it now

defined('ANGIE_PATH') or define('ANGIE_PATH', __DIR__);

// ---------------------------------------------------
//  Check PHP compatibility
// ---------------------------------------------------

if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    header('HTTP/1.1 503 Service Unavailable');
    print '<h3>Service Unavailable</h3>';
    print sprintf(
        '<p>%s requires PHP 7.4 to work. This system runs an older version (PHP %s)</p>',
        APPLICATION_NAME,
        PHP_VERSION
    );
    exit();
}

// ---------------------------------------------------
//  Low level maintenance mode message
// ---------------------------------------------------

if (defined('MAINTENANCE_MESSAGE') && MAINTENANCE_MESSAGE && !defined('IGNORE_MAINTENANCE_MESSAGE')) {
    if (php_sapi_name() == 'cli') {
        print 'In maintenance: ' . MAINTENANCE_MESSAGE . "\n";
    } else {
        header('HTTP/1.1 503 Service Unavailable');
        print '<h3>Service Unavailable</h3>';
        print '<p>Info: ' . MAINTENANCE_MESSAGE . '</p>';
        print '<p>&copy;' . date('Y');
        print '.</p>';
    }

    exit();
}

// ---------------------------------------------------
//  Patch REQUEST_URI on IIS
// ---------------------------------------------------

if (php_sapi_name() != 'cli' && !isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);

    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
}

// ---------------------------------------------------
//  Redirect in case of different ROOT_URL
// ---------------------------------------------------

// In case of web server requests make sure that requested comes from the same domain and same protocol
// as defined in ROOT_URL constant. If not, assemble url with right domain and protocol and redirect the request
// to it. This solves a lot of bugs related to the cross-domain ajax and cookie issues

if (php_sapi_name() != 'cli' && FORCE_ROOT_URL) {
    // get requested host and scheme
    $request_url = strtolower($_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
    if (strpos($request_url, ':') !== false) {
        $parsed_request_url = parse_url($request_url);
        $requested_host = isset($parsed_request_url['host']) ? $parsed_request_url['host'] : null;
        $requested_port = isset($parsed_request_url['port']) ? $parsed_request_url['port'] : 80;
    } else {
        $requested_host = $request_url;
        $requested_port = 80;
    }

    $requested_scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTP_X_REAL_PORT']) && $_SERVER['HTTP_X_REAL_PORT'] == 443)) ? 'https' : 'http';
    // get the configured host and
    $parsed_root_url = parse_url(ROOT_URL);
    $configured_host = isset($parsed_root_url['host']) ? strtolower($parsed_root_url['host']) : null;
    $configured_port = isset($parsed_root_url['port']) ? strtolower($parsed_root_url['port']) : 80;
    $configured_scheme = isset($parsed_root_url['scheme']) ? strtolower($parsed_root_url['scheme']) : null;

    //@ToDo remove
    $instance_id = defined('ON_DEMAND_INSTANCE_ID') ? ON_DEMAND_INSTANCE_ID : null;
    $should_not_skip = $instance_id !== 1;

    if (($requested_host != $configured_host || $requested_scheme != $configured_scheme) && $should_not_skip) {
        // get the request uri
        $request_uri = $_SERVER['REQUEST_URI'];
        // make sure it begins with slash
        if (substr($request_uri, 0, 1) != '/') {
            $request_uri .= '/' . $request_uri;
        }
        // assemble redirect url
        $redirect_url = $configured_scheme . '://' . $configured_host . $request_uri;
        // do the redirection
        header('HTTP/1.1 301 Moved Permanently');
        header("Location: $redirect_url");
        exit();
    }
}
// ---------------------------------------------------
//  Prepare PHP
// ---------------------------------------------------

define('CAN_USE_ZIP', extension_loaded('zlib'));

if (php_sapi_name() == 'cli') {
    set_time_limit(0); // Make sure that all CLI commands go without execution limit
}

const BUILT_IN_LOCALE = 'en_US.UTF-8';
setlocale(LC_ALL, BUILT_IN_LOCALE);

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once __DIR__ . '/classes/application/init.php'; //  Prepare application env and auto-loader

require_once __DIR__ . '/functions/general.php';
require_once __DIR__ . '/functions/files.php';
require_once __DIR__ . '/functions/utf.php';
require_once __DIR__ . '/functions/web.php';

AngieApplication::setForAutoload(
    [
        'FileDnxError' => __DIR__ . '/classes/errors/FileDnxError.class.php',
        'FileCreateError' => __DIR__ . '/classes/errors/FileCreateError.class.php',
        'FileCopyError' => __DIR__ . '/classes/errors/FileCopyError.class.php',
        'FileDeleteError' => __DIR__ . '/classes/errors/FileDeleteError.class.php',
        'DirectoryCreateError' => __DIR__ . '/classes/errors/DirectoryCreateError.class.php',
        'DirectoryDeleteError' => __DIR__ . '/classes/errors/DirectoryDeleteError.class.php',
        'DirectoryNotWritableError' => __DIR__ . '/classes/errors/DirectoryNotWritableError.class.php',
        'InvalidParamError' => __DIR__ . '/classes/errors/InvalidParamError.class.php',
        'InvalidInstanceError' => __DIR__ . '/classes/errors/InvalidInstanceError.class.php',
        'InsufficientPermissionsError' => __DIR__ . '/classes/errors/InsufficientPermissionsError.class.php',
        'NotImplementedError' => __DIR__ . '/classes/errors/NotImplementedError.class.php',
        'PhpExtensionDnxError' => __DIR__ . '/classes/errors/PhpExtensionDnxError.class.php',
        'ClassNotImplementedError' => __DIR__ . '/classes/errors/ClassNotImplementedError.class.php',
        'UploadError' => __DIR__ . '/classes/errors/UploadError.class.php',
    ],
);

// Libraries
require_once __DIR__ . '/classes/database/init.php';
require_once __DIR__ . '/classes/datetime/init.php';
