<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Public API file.
 *
 * @package activeCollab
 */
define('ANGIE_SCRIPT_TIME', microtime(true));
define('PUBLIC_PATH', DIRECTORY_SEPARATOR == '\\' ? str_replace('\\', '/', __DIR__) : __DIR__);
define('CONFIG_PATH', dirname(PUBLIC_PATH) . '/config');

const ANGIE_API_CALL = true;

if (is_file(CONFIG_PATH . '/config.php')) {
    require_once CONFIG_PATH . '/config.php';
    require_once ANGIE_PATH . '/init.php';

    AngieApplication::bootstrapForHttpRequest();
    AngieApplication::handleHttpRequest();
} else {
    header('HTTP/1.1 404 Not Found');
    die('<h1>HTTP/1.1 404 Not Found</h1>');
}
