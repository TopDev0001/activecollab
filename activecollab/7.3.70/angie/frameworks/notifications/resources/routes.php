<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

$this->mapResource(
    'notifications',
    [
        'module' => NotificationsFramework::INJECT_INTO,
    ],
    function ($collection) {
        $this->map(
            "$collection[name]_unread",
            "$collection[path]/unread",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'unread',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
        $this->map(
            "$collection[name]_object_updates",
            "$collection[path]/object-updates",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'object_updates',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
        $this->map(
            "$collection[name]_object_objets_notification",
            "$collection[path]/object-updates/:notification_id",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'notification_object_updates',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
        $this->map(
            "$collection[name]_object_updates_unread_count",
            "$collection[path]/object-updates/unread-count",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'object_updates_unread_count',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
        $this->map(
            "$collection[name]_recent_object_updates",
            "$collection[path]/object-updates/recent",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'recent_object_updates',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
        $this->map(
            "$collection[name]_mark_all_as_read",
            "$collection[path]/mark-all-as-read",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'PUT' => 'mark_all_as_read',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
    }
);
$this->map(
    'push_notification_subscribe_actions',
    'notifications/push',
    [
        'controller' => 'notifications',
        'action' => [
            'POST' => 'push_notification_subscribe',
            'PUT' => 'push_notification_unsubscribe',
        ],
        'module' => NotificationsFramework::INJECT_INTO,
    ]
);
$this->map(
    'public_notifications_subscribe',
    'public/notifications/subscribe',
    [
        'controller' => 'public_notifications',
        'action' => 'subscribe',
        'module' => NotificationsFramework::INJECT_INTO,
    ]
);
$this->map(
    'public_notifications_unsubscribe',
    'public/notifications/unsubscribe',
    [
        'controller' => 'public_notifications',
        'action' => 'unsubscribe',
        'module' => NotificationsFramework::INJECT_INTO,
    ]
);
