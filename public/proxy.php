<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

define('PUBLIC_PATH', DIRECTORY_SEPARATOR == '\\' ? str_replace('\\', '/', __DIR__) : __DIR__);
define('CONFIG_PATH', dirname(PUBLIC_PATH) . '/config');

const PROXY_HANDLER_REQUEST = true;

if (is_file(CONFIG_PATH . '/config.php')) {
    require_once CONFIG_PATH . '/config.php';
    require_once ANGIE_PATH . '/frameworks/environment/resources/proxy.php';
} else {
    header('HTTP/1.0 404 Not Found');
}
