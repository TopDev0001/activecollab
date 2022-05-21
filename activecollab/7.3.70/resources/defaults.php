<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Default configuration values.
 *
 * @package ActiveCollab.resources
 */
const APPLICATION_NAME = 'ActiveCollab';
const APPLICATION_BUILD = '%APPLICATION-BUILD%';
const APPLICATION_PATH = ROOT . '/' . APPLICATION_VERSION; // If we are using unpacked file, make sure that value is well set

defined('APPLICATION_MODE') or define('APPLICATION_MODE', 'production');
defined('ANGIE_PATH') or define('ANGIE_PATH', APPLICATION_PATH . '/angie');
defined('APPLICATION_UNIQUE_KEY') or define('APPLICATION_UNIQUE_KEY', LICENSE_KEY);
if (!defined('WAREHOUSE_URL')) {
    define(
        'WAREHOUSE_URL',
        APPLICATION_MODE === 'production' ? 'https://warehouse.activecollab.com' : 'http://warehouse.dev:8080'
    );
}

if (!defined('SHEPHERD_URL')) {
    define(
        'SHEPHERD_URL',
        APPLICATION_MODE === 'production' ? 'https://activecollab.com' : 'http://localhost:8888'
    );
}

defined('SHEPHERD_ACCESS_TOKEN') or define('SHEPHERD_ACCESS_TOKEN', 'access_token');
defined('SHEPHERD_IDP_ENDPOINT') or define('SHEPHERD_IDP_ENDPOINT', SHEPHERD_URL.'/api/v2/idp-authenticate');
defined('SHEPHERD_SAML_CRT') or define('SHEPHERD_SAML_CRT', CONFIG_PATH.'/saml.crt');
defined('SHEPHERD_SAML_KEY') or define('SHEPHERD_SAML_KEY', CONFIG_PATH.'/saml.key');
defined('IDP_NEW_SHEPHERD') or define('IDP_NEW_SHEPHERD', true);
defined('GITHUB_KEY_PATH') or define('GITHUB_KEY_PATH', '/etc/ssl/github/activecollab-integration.pem');

if (defined('IS_ON_DEMAND') && IS_ON_DEMAND) {
    if (!getenv('PASSWORD_CRYPT_HASH') && !(defined('PASSWORD_CRYPT_HASH') && PASSWORD_CRYPT_HASH)) {
        throw new InvalidArgumentException('Env PASSWORD_CRYPT_HASH is missing.');
    }

    if (!defined('PASSWORD_CRYPT_HASH')) {
        define('PASSWORD_CRYPT_HASH', getenv('PASSWORD_CRYPT_HASH'));
    }
}

defined('REDIS_HOST') or define('REDIS_HOST', getenv('REDIS_HOST'));
defined('REDIS_PORT') or define('REDIS_PORT', getenv('REDIS_PORT'));

// ---------------------------------------------------
//  Defaults MVC mapping
// ---------------------------------------------------

const DEFAULT_CONTROLLER = 'backend';

// ---------------------------------------------------
//  Frontend defaults
// ---------------------------------------------------

defined('FRONTEND_PATH') or define('FRONTEND_PATH', APPLICATION_PATH . '/frontend');

// ---------------------------------------------------
//  Load framework default configuration
// ---------------------------------------------------

require_once ANGIE_PATH . '/defaults.php';
