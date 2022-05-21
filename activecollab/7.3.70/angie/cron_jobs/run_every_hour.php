<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * Run hourly jobs (maintenance, morning mail etc).
 *
 * @package angie
 */
if (php_sapi_name() != 'cli') {
    exit("Error: CLI only\n");
}

if (isset($this) && $this instanceof \SebastianBergmann\CodeCoverage\CodeCoverage) {
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
//  Let Cron integration do the magic
// ---------------------------------------------------

/** @var CronIntegration $integration */
$integration = Integrations::findFirstByType('CronIntegration');
$integration->runEveryHour(time(), function ($message) {
    print "$message\n";
});

print 'Done in ' . ($time_to_send = round(microtime(true) - ANGIE_SCRIPT_TIME, 5)) . " seconds\n";
exit();
