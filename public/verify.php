<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Verify that this instance can be reached.
 *
 * @package ActiveCollab
 */
header('Content-Type: application/json');

$result = ['ok' => false];

if (isset($_SERVER['HTTP_X_ANGIE_VERIFY_EXISTENCE'])) {
    $hash = trim($_SERVER['HTTP_X_ANGIE_VERIFY_EXISTENCE']);

    if (strlen($hash) == 40 && preg_match('/^([a-f0-9]*)$/', $hash)) {
        $result['ok'] = true;
        $result['echo'] = $hash;
    }
}

print json_encode($result);
