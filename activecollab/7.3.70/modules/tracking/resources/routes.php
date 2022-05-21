<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;

$this->map(
    'time_records_report',
    'time-records',
    [
        'controller' => 'timesheet_report',
        'action' => [
            'GET' => 'index',
        ],
    ]
);

$this->map(
    'time_records_by_parent',
    'time-records/:parent_type/:parent_id',
    [
        'controller' => 'time_records_by_parent',
        'action' => [
            'GET' => 'index',
        ],
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
$this->map(
    'time_records_by_parent_info',
    'time-records/:parent_type/:parent_id/info',
    [
        'controller' => 'time_records_by_parent',
        'action' => [
            'GET' => 'info',
        ],
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'expenses_by_parent_info',
    'expenses/:parent_type/:parent_id/info',
    [
        'controller' => 'expenses_by_parent',
        'action' => [
            'GET' => 'info',
        ],
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'user_time_records',
    'users/:user_id/time-records',
    [
        'controller' => 'user_time_records',
        'action' => [
            'GET' => 'index',
        ],
    ],
    [
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'user_time_records_filtered_by_date',
    'users/:user_id/time-records/filtered-by-date',
    [
        'controller' => 'user_time_records',
        'action' => [
            'GET' => 'filtered_by_date',
        ],
    ],
    [
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->mapResource(
    'job_types',
    null,
    function ($collection) {
        $this->map(
            "$collection[name]_default",
            "$collection[path]/default",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'view_default',
                    'PUT' => 'set_default',
                ],
            ],
            $collection['requirements']
        );

        $this->map(
            "$collection[name]_batch_edit",
            "$collection[path]/edit-batch",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'PUT' => 'batch_edit',
                ],
            ],
            $collection['requirements']
        );
    }
);

$this->mapResource(
    'expense_categories',
    null,
    function ($collection) {
        $this->map(
            "$collection[name]_default",
            "$collection[path]/default",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'GET' => 'view_default',
                    'PUT' => 'set_default',
                ],
            ],
            $collection['requirements']
        );

        $this->map(
            "$collection[name]_batch_edit",
            "$collection[path]/edit-batch",
            [
                'controller' => $collection['controller'],
                'action' => [
                    'PUT' => 'batch_edit',
                ],
            ],
            $collection['requirements']
        );
    }
);

$this->map(
    'stopwatches_index',
    'stopwatches',
    [
        'controller' => 'stopwatch',
        'action' => [
            'GET' => 'index',
            'POST' => 'start',
        ],
    ]
);

$this->map(
    'stopwatches_offset',
    'stopwatches/offset',
    [
        'controller' => 'stopwatch',
        'action' => [
            'POST' => 'offset',
        ],
    ]
);

$this->map(
    'stopwatches_pause',
    'stopwatches/:id/pause/',
    [
        'controller' => 'stopwatch',
        'action' => [
            'PUT' => 'pause',
        ],
    ],
    [
        'id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'stopwatches_resume',
    'stopwatches/:id/resume',
    [
        'controller' => 'stopwatch',
        'action' => [
            'PUT' => 'resume',
        ],
    ],
    [
        'id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'stopwatches_delete',
    'stopwatches/:id',
    [
        'controller' => 'stopwatch',
        'action' => [
            'DELETE' => 'delete',
            'PUT' => 'edit',
        ],
    ],
    [
        'id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'internal_rate',
    'users/:user_id/internal-rate',
    [
        'controller' => 'internal_rate',
        'action' => [
            'GET' => 'view',
            'POST' => 'add',
        ],
    ],
    [
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'internal_rates',
    'users/:user_id/internal-rates',
    [
        'controller' => 'internal_rate',
        'action' => [
            'GET' => 'index',
        ],
    ],
    [
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'internal_rates_all',
    'users/internal-rates',
    [
        'controller' => 'internal_rate',
        'action' => [
            'GET' => 'all',
        ],
    ]
);

$this->map(
    'delete_internal_rate',
    'users/internal-rates/:id',
    [
        'controller' => 'internal_rate',
        'action' => [
            'DELETE' => 'delete',
        ],
    ]
);

$this->map(
    'budget_thresholds_index',
    'projects/:project_id/budget-thresholds',
    [
        'controller' => 'budget_thresholds',
        'action' => [
            'GET' => 'index',
            'POST' => 'add',
        ],
    ]
);
