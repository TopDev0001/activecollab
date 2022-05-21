<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

if (!defined('PHP_EXECUTABLE')) {
    define('PHP_EXECUTABLE', 'php');
}

define('FRONTEND_IN_PRODUCTION', defined('ASSETS_ARE_BUILT') && ASSETS_ARE_BUILT);

// ---------------------------------------------------
//  Functions definition
// ---------------------------------------------------

// DO NOT REMOVE THIS! This function is required for some PHP installations.
if (!function_exists('getallheaders')) {
    /**
     * Return all headers.
     *
     * @return array
     */
    function getallheaders()
    {
        $headers = [];

        if (!empty($_SERVER)) {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }

        return $headers;
    }
}

/**
 * Not found.
 */
function not_found()
{
    header('HTTP/1.1 404 Not Found');
    exit();
}

/**
 * Bad request.
 */
function bad_request()
{
    header('HTTP/1.1 400 Bad Request');
    exit();
}

function is_valid_version($version) {
    if (is_string($version) && $version) {
        if ($version === 'current') {
            return true;
        }

        $version_bits = explode('.', $version);

        if (count($version_bits) === 3) {
            foreach ($version_bits as $version_bit) {
                if (!ctype_digit($version_bit)) {
                    return false;
                }
            }

            return true;
        }
    }

    return false;
}

/**
 * Check if etag of file matches the one.
 *
 * @param  string $etag
 * @return bool
 */
function etag_valid($etag)
{
    $if_none_match = get_request_etag();

    return $etag && $etag == $if_none_match;
}

/**
 * Get request etag.
 *
 * @return string
 */
function get_request_etag()
{
    foreach (getallheaders() as $header_name => $header_value) {
        if (strtolower($header_name) == 'if-none-match') {
            return $header_value;
        }
    }

    return null;
}

/**
 * Get etag for file.
 *
 * @param  string $file
 * @return string
 */
function get_asset_etag($file)
{
    if (!is_file($file)) {
        return null;
    }

    // if in development we do the md5 of the whole file
    if (!FRONTEND_IN_PRODUCTION) {
        return md5_file($file);
    }

    // if in production we do the much resource friendly check
    return md5(filemtime($file) . $file);
}

/**
 * Execute cli command.
 *
 * @param string $command
 */
function execute_cli($command)
{
    $current_directory = getcwd();
    if (defined('IS_LEGACY_DEV') && IS_LEGACY_DEV) {
        chdir(realpath(ROOT . '/..'));
    } else {
        chdir(realpath(ROOT . '/../../activecollab'));
    }

    $cli_exit_code = 0;
    $cli_exit_messages = [];

    exec(PHP_EXECUTABLE . ' tasks/activecollab-cli.php ' . $command, $cli_exit_messages, $cli_exit_code);

    if ($cli_exit_code != 0) {
        echo implode("\n", $cli_exit_messages);
        exit();
    }

    chdir($current_directory);
}

/**
 * Serve file.
 *
 * @param string $file
 * @param string $custom_etag
 */
function serve_file($file, $custom_etag = null)
{
    header('Etag: ' . ($custom_etag ?: get_asset_etag($file)));
    echo file_get_contents($file);
    exit();
}

/**
 * Serve not modified.
 *
 * @param string $etag
 */
function serve_not_modified($etag)
{
    header("Etag: $etag");
    header('HTTP/1.1 304 Not Modified');
    exit();
}

/**
 * Get language.
 *
 * @param  string $default_language
 * @return string
 */
function get_language($default_language)
{
    // load language
    $language_cookie_name = 'activecollab_ul_for_' . sha1(ROOT_URL);
    if (!empty($_COOKIE[$language_cookie_name])) {
        // get the language from the cookie if cookie exists
        $language = $_COOKIE[$language_cookie_name];
    } else {
        // get the default language from the database
        $language = $default_language;
        if ($connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
            $connection->set_charset('utf8mb4');
            if ($result = $connection->query('SELECT locale from languages WHERE is_default = 1 LIMIT 0,1')) {
                $result = $result->fetch_assoc();
                if (!empty($result['locale'])) {
                    $language = $result['locale'];
                }
            }
        }
    }

    return $language;
}

// different init depending on if in production
if (!FRONTEND_IN_PRODUCTION) {
    $javascript_directory = COMPILE_PATH;
    $css_directory = COMPILE_PATH;
} else {
    $dir = dirname(__FILE__);
    $javascript_directory = $dir . '/javascript';
    $css_directory = $dir . '/css';
}

/*
 * Serve css file
 *
 * @var function
 */
$serve_css = function ($filename, $compile_command) use ($css_directory) {
    header('Content-Type: text/css');
    $compiled_file = COMPILE_PATH . '/' . $filename;

    if (!FRONTEND_IN_PRODUCTION) {
        $file_etag = get_asset_etag($compiled_file);

        if (etag_valid($file_etag)) {
            serve_not_modified($file_etag);
        } else {
            execute_cli($compile_command);
            serve_file($compiled_file);
        }
    } else {
        $source_file = $css_directory . '/' . $filename;
        $file_etag = get_asset_etag($source_file);

        if (etag_valid($file_etag)) {
            serve_not_modified($file_etag);
        } else {
            file_put_contents($compiled_file, str_replace('--ASSETS-URL--', ASSETS_URL, file_get_contents($source_file)));
            serve_file($compiled_file, $file_etag);
        }
    }
};

/*
 * Serve javascript file
 *
 * @var function
 */
$serve_javascript = function ($filename, $compile_command) use ($javascript_directory) {
    header('Content-Type: text/javascript');

    if (!FRONTEND_IN_PRODUCTION) {
        $compiled_file = COMPILE_PATH . '/' . $filename;
        $file_etag = get_asset_etag($compiled_file);

        if (etag_valid($file_etag)) {
            serve_not_modified($file_etag);
        } else {
            execute_cli($compile_command);
            serve_file($compiled_file);
        }
    } else {
        $source_file = $javascript_directory . '/' . $filename;
        $file_etag = get_asset_etag($source_file);

        if (etag_valid($file_etag)) {
            serve_not_modified($file_etag);
        } else {
            serve_file($source_file);
        }
    }
};

// =======================
// = Start assets output =
// =======================

$zlib_compression_on = (bool) ini_get('zlib.output_compression');
if (!$zlib_compression_on) {
    ob_start('ob_gzhandler');
}

// necessary headers
header('Cache-Control: no-store, max-age=0');
header('X-Angie-ApplicationVersion: ' . APPLICATION_VERSION);

$default_language = 'en_US.UTF-8';

if (!defined('IS_ON_DEMAND') && array_key_exists('version', $_GET) && !is_valid_version($_GET['version'])) {
    bad_request();
}

$resource = isset($_GET['resource']) && $_GET['resource'] ? strtolower($_GET['resource']) : 'main_layout';

// main_layout
if ($resource == 'main_layout') {
    header('Content-Type: text/html');

    $csrf_validator_name = 'activecollab_csrf_validator_for_' . sha1(ROOT_URL);
    $loaded_language = get_language($default_language);

    $should_use_new_cdn = true; // defined('ON_DEMAND_APPLICATION_CHANNEL') && ON_DEMAND_APPLICATION_CHANNEL === 2;

    // template replacements
    $replacements = [
        '--API-URL--' => ROOT_URL . '/api/v1',
        '--ROOT-URL--' => ROOT_URL,
        '--CSRF-VALIDATOR-NAME--' => $csrf_validator_name,
        '--APPLICATION-VERSION--' => APPLICATION_VERSION,
        '--LOADED-LANGUAGE--' => $loaded_language,
        '--PRELOADER-MODE--' => (empty($_COOKIE[$csrf_validator_name]) ? 'unauthorized' : 'authorized'),
        '--CDN-URL--' => $should_use_new_cdn
            ? '//assets.activecollab.com/feather/' . APPLICATION_VERSION
            : '//cdn.activecollab.com/feather/' . APPLICATION_VERSION,
    ];

    $is_on_demand = defined('IS_ON_DEMAND') && IS_ON_DEMAND;
    $is_in_development = defined('APPLICATION_MODE') && APPLICATION_MODE == 'development';
    $is_in_test = defined('ANGIE_IN_TEST') && ANGIE_IN_TEST;

    // depending on deployment generate links to assets
    if ($is_on_demand && !$is_in_development && !$is_in_test) {
        $cdn_url_base = $replacements['--CDN-URL--'];
        $asset_replacements = [
            '--ASSETS-URL--' => $cdn_url_base . '/assets',
            '--ASSET-FONT-URL--' => $should_use_new_cdn
                ? '//assets.activecollab.com/fonts/clear-sans'
                : '//cdn.activecollab.com/fonts/clear-sans',
            '--ASSET-LIBRARY-CSS-URL--' => $cdn_url_base . '/css/libraries.css',
            '--ASSET-LIBRARY-JS-URL--' => $cdn_url_base . '/javascript/libraries.js',
            '--ASSET-APPLICATION-CSS-URL--' => $cdn_url_base . '/css/application.css',
            '--ASSET-APPLICATION-JS-URL--' => $cdn_url_base . '/javascript/application.' . $loaded_language . '.js',
        ];
    } else {
        $asset_replacements = [
            '--ASSETS-URL--' => ASSETS_URL,
            '--ASSET-FONT-URL--' => ASSETS_URL . '/system/fonts',
            '--ASSET-LIBRARY-CSS-URL--' => ROOT_URL . '/index.php?resource=libraries_css&version=' . APPLICATION_VERSION,
            '--ASSET-LIBRARY-JS-URL--' => ROOT_URL . '/index.php?resource=libraries_js&version=' . APPLICATION_VERSION,
            '--ASSET-APPLICATION-CSS-URL--' => ROOT_URL . '/index.php?resource=application_css&version=' . APPLICATION_VERSION,
            '--ASSET-APPLICATION-JS-URL--' => ROOT_URL . '/index.php?resource=application_js&version=' . APPLICATION_VERSION . '&language=' . $loaded_language,
            '--ASSET-REACT-JS-URL--' => ASSETS_URL . '/react-view/module.js',
        ];
    }

    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : null;

    if (
        $user_agent &&
        (
            strpos($user_agent, 'macintosh') !== false ||
            strpos($user_agent, 'iphone') !== false ||
            strpos($user_agent, 'ipad') !== false
        )
    ) {
        $replacements['--CUSTOM-FONTS--'] = '';
    } else {
        $replacements['--CUSTOM-FONTS--'] = file_get_contents(FRONTEND_PATH . '/custom_fonts.css');
    }

    $replacements['--BASE-STYLES--'] = file_get_contents(FRONTEND_PATH . '/base_styles.css');
    $replacements['--ON-DEMAND--'] = $is_on_demand ? 'true' : 'false'; // need to pass this because of public pages (they can't get is on demand value from initial)

    // join asset replacements with other replacements
    $replacements = array_merge($replacements, $asset_replacements);

    // render layout
    $main_layout = file_get_contents(FRONTEND_PATH . '/wireframe.html');
    foreach ($replacements as $replacement_key => $replacement_value) {
        $main_layout = str_replace($replacement_key, $replacement_value, $main_layout);
    }
    echo $main_layout;

    // application_css
} elseif ($resource == 'application_css') {
    $serve_css('application.css', 'dev:compile_application_asset -s');

    // application_js
} elseif ($resource == 'application_js') {
    $language = !empty($_GET['language']) ? $_GET['language'] : $default_language;
    $serve_javascript('application.' . $language . '.js', 'dev:compile_application_asset -j --language="' . $language . '"');

    // libraries_css
} elseif ($resource == 'libraries_css') {
    $serve_css('libraries.css', 'dev:compile_vendor_asset -s');

    // libraries_js
} elseif ($resource == 'libraries_js') {
    $serve_javascript('libraries.js', 'dev:compile_vendor_asset -j');

    // component_js
} elseif ($resource == 'component_js') {
    $serve_javascript($_GET['component'] . '.js', 'dev:compile_component_asset -j --component="' . $_GET['component'] . '"');

    // component_css
} elseif ($resource == 'component_css') {
    $serve_css($_GET['component'] . '.css', 'dev:compile_component_asset -s --component="' . $_GET['component'] . '"');

    // invalid request
} else {
    not_found();
}

if (!$zlib_compression_on) {
    ob_end_flush();
}
