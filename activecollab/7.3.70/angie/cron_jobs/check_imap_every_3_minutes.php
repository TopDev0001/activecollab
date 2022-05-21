<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;

if (php_sapi_name() != 'cli') {
    exit("Error: CLI only\n");
}

if (isset($this) && $this instanceof CodeCoverage) {
    return;
}

// ---------------------------------------------------
//  Kill the limits
// ---------------------------------------------------

set_time_limit(0);

if (in_array(APPLICATION_BUILD, ['development', 'debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', 0);
}

// Bootstrap for command line, with router, events and modules
AngieApplication::bootstrapForCommandLineRequest();

// ---------------------------------------------------
//  Remember last trigger time
// ---------------------------------------------------

$timestamp = time();

if (DB::executeFirstCell("SELECT COUNT(`id`) AS 'row_count' FROM `memories` WHERE `key` = 'check_imap_last_run'")) {
    DB::execute(
        "UPDATE `memories` SET `value` = ?, `updated_on` = ? WHERE `key` = 'check_imap_last_run'",
        serialize($timestamp),
        date('Y-m-d H:i:s')
    );
} else {
    DB::execute(
        "INSERT INTO `memories` (`key`, `value`, `updated_on`) VALUES ('check_imap_last_run', ?, ?)",
        serialize($timestamp),
        date('Y-m-d H:i:s')
    );
}

IncomingMail::checkImap();
print 'Done in ' . ($time_to_send = round(microtime(true) - ANGIE_SCRIPT_TIME, 5)) . " seconds\n";
exit();
