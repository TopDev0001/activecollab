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
    die("Error: CLI only\n");
}

if (DIRECTORY_SEPARATOR == '\\') {
    define('PUBLIC_PATH', str_replace('\\', '/', dirname(dirname(__DIR__)) . '/public'));
} else {
    define('PUBLIC_PATH', dirname(dirname(__DIR__)) . '/public');
}

// Load configuration and initialize framework
require_once dirname(PUBLIC_PATH) . '/config/config.php';
require_once ANGIE_PATH . '/init.php';
require_once ANGIE_PATH . '/cron_jobs/check_imap_every_3_minutes.php';
