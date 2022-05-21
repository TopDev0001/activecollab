<?php

/*
 * This file is part of the Shepherd project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

defined('APPLICATION_UNIQUE_KEY') or define('APPLICATION_UNIQUE_KEY', '8e6a28bbfe6169fa14142ab2d7a90b9544e3a042');
defined('LICENSE_KEY') or define('LICENSE_KEY', 'laeuhXf5M1DhhpCBfPL5xBPVZ8kI0irOYrhMLVoO/11901');
defined('APPLICATION_MODE') or define('APPLICATION_MODE', 'production'); // set to 'development' if you need to debug installer
defined('CONFIG_PATH') or define('CONFIG_PATH', __DIR__);
defined('ROOT') or define('ROOT', dirname(CONFIG_PATH) . '/activecollab');
defined('ROOT_URL') or define('ROOT_URL', 'http://activecollab.dev/public');
defined('FORCE_ROOT_URL') or define('FORCE_ROOT_URL', false);
require_once CONFIG_PATH . '/version.php';
require_once CONFIG_PATH . '/defaults.php';
