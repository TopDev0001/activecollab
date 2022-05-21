<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Initial datetime values.
 *
 * @package angie.library.datetime
 */
const DATE_MYSQL = 'Y-m-d';
const DATETIME_MYSQL = 'Y-m-d H:i:s';

require_once __DIR__ . '/DateValue.class.php';
require_once __DIR__ . '/DateTimeValue.class.php';

ini_set('date.timezone', 'GMT');
date_default_timezone_set('GMT');
