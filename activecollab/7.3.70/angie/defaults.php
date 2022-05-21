<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * Default configuration options.
 *
 * Options listed in this file can be overriden by the application via config/config.php or application level
 * defaults.php file
 *
 * @package angie
 */
if (!defined('ROOT_URL') && php_sapi_name() == 'cli') {
    define('ROOT_URL', 'unknown'); // In case we are executing this command via CLI, for testing and initialization
}

defined('ASSETS_ARE_BUILT') or define('ASSETS_ARE_BUILT', true);

defined('ANGIE_SCRIPT_TIME') or define('ANGIE_SCRIPT_TIME', microtime(true));
defined('ADMIN_EMAIL') or define('ADMIN_EMAIL', false);
defined('TABLE_PREFIX') or define('TABLE_PREFIX', '');
defined('MAILING_ADAPTER') or define('MAILING_ADAPTER', 'queued');
defined('FORCE_ROOT_URL') or define('FORCE_ROOT_URL', true);

defined('URL_BASE') or define('URL_BASE', ROOT_URL . '/');
defined('ASSETS_URL') or define('ASSETS_URL', ROOT_URL . '/assets');

defined('PUBLIC_PATH') or define('PUBLIC_PATH', realpath(ROOT . '/../instance/public'));
defined('ASSETS_PATH') or define('ASSETS_PATH', PUBLIC_PATH . '/assets');

defined('FORCE_INTERFACE') or define('FORCE_INTERFACE', false);
defined('FORCE_DEVICE_CLASS') or define('FORCE_DEVICE_CLASS', false);

defined('PURIFY_HTML') or define('PURIFY_HTML', true);
defined('REMOVE_EMPTY_PARAGRAPHS') or define('REMOVE_EMPTY_PARAGRAPHS', true);
defined('MAINTENANCE_MESSAGE') or define('MAINTENANCE_MESSAGE', null);
defined('CREATE_THUMBNAILS') or define('CREATE_THUMBNAILS', true);
defined('RESIZE_SMALLER_THAN') or define('RESIZE_SMALLER_THAN', 524288);
defined('IMAGE_SIZE_CONSTRAINT') or define('IMAGE_SIZE_CONSTRAINT', '2240x1680');
defined('COMPRESS_HTTP_RESPONSES') or define('COMPRESS_HTTP_RESPONSES', true);
defined('COMPRESS_ASSET_REQUESTS') or define('COMPRESS_ASSET_REQUESTS', true);
defined('PAGE_PLACEHOLDER') or define('PAGE_PLACEHOLDER', '-PAGE-');
defined('NUMBER_FORMAT_DEC_SEPARATOR') or define('NUMBER_FORMAT_DEC_SEPARATOR', '.');
defined('NUMBER_FORMAT_THOUSANDS_SEPARATOR') or define('NUMBER_FORMAT_THOUSANDS_SEPARATOR', ',');
defined('DEFAULT_CSV_SEPARATOR') or define('DEFAULT_CSV_SEPARATOR', ',');
defined('CACHE_PATH') or define('CACHE_PATH', ENVIRONMENT_PATH . '/cache');
defined('COLLECTOR_CHECK_ETAG') or define('COLLECTOR_CHECK_ETAG', true);
defined('SEARCH_INDEX_FILES') or define('SEARCH_INDEX_FILES', defined('ANGIE_IN_TEST') && ANGIE_IN_TEST); // TRUE only for testing
defined('DISCOVER_PHP_CLI') or define('DISCOVER_PHP_CLI', false);
defined('INSTALLER_USE_PHP_SELF') or define('INSTALLER_USE_PHP_SELF', false);

defined('USE_CACHE') or define('USE_CACHE', true);
defined('CACHE_BACKEND') or define('CACHE_BACKEND', 'FileCacheBackend');
defined('CACHE_LIFETIME') or define('CACHE_LIFETIME', 43200);

// ---------------------------------------------------
//  MVC elements
// ---------------------------------------------------

defined('DEFAULT_MODULE') or define('DEFAULT_MODULE', 'system');
defined('DEFAULT_CONTROLLER') or define('DEFAULT_CONTROLLER', 'backend');
defined('DEFAULT_ACTION') or define('DEFAULT_ACTION', 'index');
defined('DEFAULT_FORMAT') or define('DEFAULT_FORMAT', 'html');

// ---------------------------------------------------
//  Date and time froms
// ---------------------------------------------------

// Formats can be overriden with constants with same name that start with
// USER_ (USER_FORMAT_DATE will override FORMAT_DATE)
if (DIRECTORY_SEPARATOR == '\\') {
    defined('FORMAT_DATETIME') or define('FORMAT_DATETIME', '%b %#d. %Y, %I:%M %p');
    defined('FORMAT_DATE') or define('FORMAT_DATE', '%b %#d. %Y');
} else {
    defined('FORMAT_DATETIME') or define('FORMAT_DATETIME', '%b %e. %Y, %I:%M %p');
    defined('FORMAT_DATE') or define('FORMAT_DATE', '%b %e. %Y');
}

defined('FORMAT_TIME') or define('FORMAT_TIME', '%I:%M %p');

// ---------------------------------------------------
//  Environment and paths
// ---------------------------------------------------
defined('IS_LEGACY_DEV') or define('IS_LEGACY_DEV', false);
defined('ENVIRONMENT') or define('ENVIRONMENT', substr(ENVIRONMENT_PATH, strrpos(ENVIRONMENT_PATH, '/') + 1)); // Read environment name from environment path
defined('COMPILE_PATH') or define('COMPILE_PATH', ENVIRONMENT_PATH . '/compile');
defined('DEVELOPMENT_PATH') or define('DEVELOPMENT_PATH', ROOT . '/development');
defined('UPLOAD_PATH') or define('UPLOAD_PATH', ENVIRONMENT_PATH . '/upload');
defined('LIMIT_DISK_SPACE_USAGE') or define('LIMIT_DISK_SPACE_USAGE', null);
defined('CUSTOM_PATH') or define('CUSTOM_PATH', ENVIRONMENT_PATH . '/custom');
defined('IMPORT_PATH') or define('IMPORT_PATH', ENVIRONMENT_PATH . '/import');
defined('THUMBNAILS_PATH') or define('THUMBNAILS_PATH', ENVIRONMENT_PATH . '/thumbnails');
defined('WORK_PATH') or define('WORK_PATH', ENVIRONMENT_PATH . '/work');
