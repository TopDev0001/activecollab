<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Instance dependent defaults file.
 *
 * @package ActiveCollab
 */
defined('ENVIRONMENT_PATH') or define('ENVIRONMENT_PATH', str_replace('\\', '/', dirname(__DIR__)));

require_once ROOT . '/' . APPLICATION_VERSION . '/resources/defaults.php';
