<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;

$this->map(
    'subscribers',
    'subscribers/:parent_type/:parent_id',
    [
        'action' => [
            'GET' => 'index',
            'POST' => 'bulk_subscribe',
            'PUT' => 'bulk_update',
            'DELETE' => 'bulk_unsubscribe',
        ],
        'controller' => 'subscribers',
        'module' => SubscriptionsFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'subscriber',
    'subscribers/:parent_type/:parent_id/users/:user_id',
    [
        'action' => [
            'POST' => 'subscribe',
            'DELETE' => 'unsubscribe',
        ],
        'controller' => 'subscribers',
        'module' => SubscriptionsFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
