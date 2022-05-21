<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.tasks
 */
if (php_sapi_name() != 'cli') {
    print "This script is available via CLI only\n";
    exit(1);
}

if (DIRECTORY_SEPARATOR == '\\') {
    define('PUBLIC_PATH', str_replace('\\', '/', dirname(__DIR__) . '/public'));
} else {
    define('PUBLIC_PATH', dirname(__DIR__) . '/public');
}

if (is_file(dirname(__DIR__) . '/config/config.php')) {
    require_once dirname(__DIR__) . '/config/config.php';
} else {
    require_once dirname(__DIR__) . '/config/config.empty.php';
}

require_once ANGIE_PATH . '/init.php';
require_once ANGIE_PATH . '/cli.php';
