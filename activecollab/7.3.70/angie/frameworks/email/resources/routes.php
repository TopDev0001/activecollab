<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

$this->map(
    'email_integration_email_log',
    'integrations/email/email-log',
    [
        'module' => EmailFramework::INJECT_INTO,
        'controller' => 'email_integration',
        'action' => [
            'GET' => 'email_log',
        ],
        'integration_type' => 'email',
    ]
);

$this->map(
    'email_integration_test_connection',
    'integrations/email/test-connection',
    [
        'module' => EmailFramework::INJECT_INTO,
        'controller' => 'email_integration',
        'action' => [
            'POST' => 'test_connection',
        ],
        'integration_type' => 'email',
    ]
);
