<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Initialization file of HTTP package.
 */
define('HTTP_LIB_PATH', ANGIE_PATH . '/classes/http');

require_once HTTP_LIB_PATH . '/HTTP.class.php';
require_once HTTP_LIB_PATH . '/HTTP_Header.class.php';
require_once HTTP_LIB_PATH . '/HTTP_Download.class.php';
