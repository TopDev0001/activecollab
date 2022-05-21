<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;

$this->map(
    'reminders',
    'reminders/:parent_type/:parent_id',
    [
        'module' => RemindersFramework::INJECT_INTO,
        'controller' => 'reminders',
        'action' => [
            'GET' => 'index',
            'POST' => 'add',
        ],
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'reminder',
    'reminders/:reminder_id',
    [
        'module' => RemindersFramework::INJECT_INTO,
        'controller' => 'reminders',
        'action' => [
            'DELETE' => 'delete',
        ],
    ],
    [
        'reminder_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
