<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;

$this->map(
    'team_tasks',
    'teams/:team_id/tasks',
    [
        'controller' => 'team_tasks',
        'action' => [
            'GET' => 'index',
        ],
    ],
    [
        'team_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
$this->map(
    'user_tasks',
    'users/:user_id/tasks',
    [
        'controller' => 'user_tasks',
        'action' => [
            'GET' => 'index',
        ],
    ],
    [
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'unscheduled_task_counts',
    'reports/unscheduled-tasks/count-by-project',
    [
        'controller' => 'unscheduled_tasks',
        'action' => [
            'GET' => 'count_by_project',
        ],
    ]
);

$this->map(
    'task_dependencies',
    'dependencies/tasks/:task_id',
    [
        'controller' => 'task_dependencies',
        'action' => [
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'delete',
        ],
    ],
    [
        'task_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'project_task_dependencies',
    'dependencies/project/:project_id',
    [
        'controller' => 'project_dependencies',
        'action' => [
            'GET' => 'view',
        ],
    ],
    [
        'project_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'task_dependency_suggestions',
    'dependencies/tasks/:task_id/suggestions',
    [
        'controller' => 'task_dependencies',
        'action' => [
            'GET' => 'dependency_suggestions',
        ],
    ],
    [
        'task_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'task_reschedule',
    'tasks/:task_id/reschedule',
    [
        'controller' => 'task_reschedule',
        'action' => [
            'GET' => 'reschedule_simulation',
            'POST' => 'make_reschedule',
        ],
    ],
    [
        'task_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
