<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

// Check minimal PHP version before we hit syntax errors in framework files
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    print '<h1>ActiveCollab Error</h1>';
    print sprintf(
        '<p>ActiveCollab requires PHP 7.4 or newer, but you appear to have PHP %s. Please upgrade your PHP.</p>',
        version_compare(PHP_VERSION, '5.2.7', '>=')
            ? PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION
            : PHP_VERSION,
    );
    print '<p style="text-align: center; margin-top: 50px;">&copy; 2007-' . date('Y') . ' <a href="https://www.activecollab.com">ActiveCollab</a> &mdash; powerful, yet simple project and task management.</p>';

    exit();
}

define('ANGIE_SCRIPT_TIME', microtime(true));
define('PUBLIC_PATH', DIRECTORY_SEPARATOR == '\\' ? str_replace('\\', '/', __DIR__) : __DIR__);
define('CONFIG_PATH', dirname(PUBLIC_PATH) . '/config');

if (file_exists(CONFIG_PATH . '/config.php')) {
    require_once CONFIG_PATH . '/config.php';

    defined('FRONTEND_PATH') or define('FRONTEND_PATH', APPLICATION_PATH . '/frontend');

    require_once FRONTEND_PATH . '/frontend.php';
} else {
    require 'install.php';
}
