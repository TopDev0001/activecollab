<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

$this->mapResource(
    'calendars',
    [
        'module' => CalendarsFramework::INJECT_INTO,
    ],
    function (array $collection, array $single) {
        $this->map(
            "$collection[name]_events",
            "$collection[path]/events",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'all_calendar_events',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
        $this->mapResource(
            'calendar_events',
            [
                'module' => $collection['module'],
                'collection_path' => "$single[path]/events",
                'collection_requirements' => $collection['requirements'],
            ]
        );
    }
);
