<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Installation interface.
 *
 * @package ActiveCollab
 */

// Check if this file was included by router.php or access directly
if (!defined('PUBLIC_PATH') || !defined('CONFIG_PATH')) {
    print '<h1>ActiveCollab Installer Error</h1>';
    print "<p>This file should <u>not be accessed directly</u>. Simply visit the root folder where you've uploaded the ActiveCollab files and the installer will guide you through the installation process.</p>";
    print '<p><u>Not seeing the installer</u> when you do that? Please configure <a href="https://help.activecollab.com/books/self-hosted/installation.html#s-url-rewriting">URL rewiritng</a>.</p>';
    print '<p style="text-align: center; margin-top: 50px;">&copy; 2007-' . date('Y') . ' <a href="https://www.activecollab.com">ActiveCollab</a> &mdash; powerful, yet simple project and task management.</p>';

    die();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists(CONFIG_PATH . '/config.php')) {
    die('<p><a href="https://www.activecollab.com/index.html">ActiveCollab</a> is already installed</p>');
} else {
    require_once CONFIG_PATH . '/config.empty.php';
    require_once ANGIE_PATH . '/init.php';

    AngieApplication::bootstrapForHttpRequest();

    if (isset($_POST['submitted']) && $_POST['submitted'] == 'submitted' && $_POST['installer_section']) {
        AngieApplicationInstaller::executeSection($_POST['installer_section'], $_POST);
    } else {
        AngieApplicationInstaller::render();
    }
}
