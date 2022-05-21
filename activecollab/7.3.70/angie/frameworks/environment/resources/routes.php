<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;

$this->map(
    'api_info',
    'info',
    [
        'controller' => 'utilities',
        'action' => [
            'GET' => 'info',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'initial',
    'initial',
    [
        'controller' => 'initial',
        'action' => 'index',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'initial_speed_test',
    'initial/test-speed',
    [
        'controller' => 'initial',
        'action' => 'test_action_speed',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'system_status',
    'system-status',
    [
        'controller' => 'system_status',
        'action' => 'index',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'check_for_updates',
    'system-status/check-for-updates', [
        'controller' => 'system_status',
        'action' => 'check_for_updates',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'download_release',
    'system-status/download-release', [
        'controller' => 'system_status',
        'action' => [
                'GET' => 'get_download_progress',
                'POST' => 'start_download',
            ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'check_environment',
    'system-status/check-environment', [
        'controller' => 'system_status',
        'action' => 'check_environment',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'upgrade',
    'upgrade', [
        'controller' => 'upgrade',
        'action' => [
            'GET' => 'index',
            'POST' => 'finish',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'upgrade_release_notes',
    'upgrade/release-notes',
    [
        'controller' => 'upgrade',
        'action' => 'release_notes',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'system_notifications_dismiss',
    '/system-notifications/:notification_id/dismiss',
    [
        'controller' => 'system_notifications',
        'action' => [
            'GET' => 'dismiss',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

// ---------------------------------------------------
//  State
// ---------------------------------------------------

$this->map(
    'trash',
    'trash',
    [
        'action' => [
            'GET' => 'show_content',
            'DELETE' => 'empty_trash',
        ],
        'controller' => 'trash',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'move_to_archive',
    'move-to-archive/:parent_type/:parent_id',
    [
        'action' => [
            'PUT' => 'archive',
        ],
        'controller' => 'state',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'restore_from_archive',
    'restore-from-archive/:parent_type/:parent_id',
    [
        'action' => [
            'PUT' => 'restore_from_archive',
        ],
        'controller' => 'state',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'move_to_trash',
    'move-to-trash/:parent_type/:parent_id',
    [
        'action' => [
            'PUT' => 'trash',
        ],
        'controller' => 'state',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'restore_from_trash',
    'restore-from-trash/:parent_type/:parent_id',
    [
        'action' => [
            'PUT' => 'restore_from_trash',
        ],
        'controller' => 'state',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'permanently_delete',
    'permanently-delete/:parent_type/:parent_id',
    [
        'action' => [
            'DELETE' => 'permanently_delete',
        ],
        'controller' => 'state',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'reactivate',
    'reactivate/:parent_type/:parent_id',
    [
        'action' => [
            'PUT' => 'reactivate',
        ],
        'controller' => 'state',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'complete',
    'complete/:parent_type/:parent_id',
    [
        'action' => [
            'PUT' => 'complete',
        ],
        'controller' => 'complete',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'open',
    'open/:parent_type/:parent_id',
    [
        'action' => [
            'PUT' => 'open',
        ],
        'controller' => 'complete',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

// ---------------------------------------------------
//  Utility
// ---------------------------------------------------

$this->map(
    'compare_text',
    'compare-text',
    [
        'action' => [
            'POST' => 'compare',
        ],
        'controller' => 'compare_text',
        'module' => EnvironmentFramework::INJECT_INTO, ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'access_logs',
    'access-logs/:parent_type/:parent_id',
    [
        'action' => [
            'GET' => 'index',
            'PUT' => 'log_access',
        ],
        'controller' => 'access_logs',
        'module' => EnvironmentFramework::INJECT_INTO,
    ], [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'upload_files',
    'upload-files',
    [
        'action' => [
            'POST' => 'index',
            'GET' => 'prepare',
        ],
        'controller' => 'upload_files',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

// ---------------------------------------------------
//  Integrations
// ---------------------------------------------------

$this->map(
    'integration_singletons',
    'integrations/:integration_type',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
        'controller' => 'integration_singletons',
        'action' => [
            'GET' => 'get',
            'PUT' => 'set',
            'DELETE' => 'forget',
        ],
    ],
    [
        'integration_type' => UrlMatcherInterface::MATCH_SLUG,
    ]
);

$this->mapResource(
    'integrations',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'cron_integration',
    'integrations/cron',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
        'controller' => 'cron_integration',
        'action' => 'get',
        'integration_type' => 'cron',
    ]
);

// ---------------------------------------------------
//  Search
// ---------------------------------------------------

$this->map(
    'search',
    'search',
    [
        'action' => [
            'GET' => 'query',
        ],
        'controller' => 'search',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'search_integration_configure',
    'integrations/search/configure',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
        'controller' => 'search_integration',
        'action' => [
            'PUT' => 'configure',
        ],
        'integration_type' => 'search',
    ]
);

$this->map(
    'search_integration_test_connection',
    'integrations/search/test-connection',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
        'controller' => 'search_integration',
        'action' => [
            'POST' => 'test_connection',
        ],
        'integration_type' => 'search',
    ]
);

$this->map(
    'search_integration_disconnect',
    'integrations/search/disconnect',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
        'controller' => 'search_integration',
        'action' => [
            'POST' => 'disconnect',
        ],
        'integration_type' => 'search',
    ]
);

// ---------------------------------------------------
//  Localization
// ---------------------------------------------------

$this->mapResource(
    'languages',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
        'collection_actions' => [
            'GET' => 'index',
        ],
        'single_actions' => [
            'GET' => 'view',
        ],
    ],
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
                'module' => EnvironmentFramework::INJECT_INTO, ],
            $collection['requirements']
        );
    }
);

$this->map(
    'localization',
    'localization',
    [
        'controller' => 'localization',
        'action' => [
            'GET' => 'show_settings',
            'PUT' => 'save_settings',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'localization_timezones',
    'localization/timezones',
    [
        'controller' => 'localization',
        'action' => [
            'GET' => 'show_timezones',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'localization_date_formats',
    'localization/date-formats',
    [
        'controller' => 'localization',
        'action' => [
            'GET' => 'show_date_formats',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'localization_time_formats',
    'localization/time-formats',
    [
        'controller' => 'localization',
        'action' => [
            'GET' => 'show_time_formats',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'localization_countries',
    'localization/countries',
    [
        'controller' => 'localization',
        'action' => [
            'GET' => 'show_countries',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'localization_eu_countries',
    'localization/eu-countries',
    [
        'controller' => 'localization',
        'action' => [
            'GET' => 'show_eu_countries',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'localization_states',
    'localization/states',
    [
        'controller' => 'localization',
        'action' => [
                'GET' => 'show_states',
            ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

// ---------------------------------------------------
//  Config options
// ---------------------------------------------------

$this->map(
    'config_options',
    'config-options',
    [
        'action' => [
            'GET' => 'get',
            'PUT' => 'set',
        ],
        'controller' => 'config_options',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'personalized_config_options',
    'personalized-config-options',
    [
        'action' => [
            'GET' => 'personalized_get',
            'PUT' => 'personalized_set',
        ],
        'controller' => 'config_options',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->mapResource(
    'currencies',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
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
                'module' => EnvironmentFramework::INJECT_INTO,
            ],
            $collection['requirements']
        );
    }
);

$this->map(
    'workweek',
    'workweek',
    [
        'controller' => 'workweek',
        'action' => [
            'GET' => 'show_settings',
            'PUT' => 'save_settings',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'reports',
    'reports',
    [
        'controller' => 'reports',
        'action' => [
            'GET' => 'index',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'reports_run',
    'reports/run',
    [
        'controller' => 'reports',
        'action' => [
            'GET' => 'run',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'reports_export',
    'reports/export',
    [
        'controller' => 'reports',
        'action' => [
            'GET' => 'export',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->mapResource(
    'day_offs',
    [
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->mapResource(
    'data_filters', [
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'favorites',
    'favorites',
    [
        'action' => [
            'GET' => 'index',
        ],
        'controller' => 'favorites',
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'favorite',
    'favorites/:parent_type/:parent_id',
    [
        'action' => [
            'GET' => 'check',
            'PUT' => 'add',
            'DELETE' => 'remove',
        ],
        'controller' => 'favorites',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'warehouse_pingback',
    'integrations/warehouse/pingback',
    [
        'controller' => 'warehouse',
        'action' => [
            'POST' => 'pingback',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);
$this->map(
    'warehouse_store_export_complete_pingback',
    'integrations/warehouse/store/export',
    [
        'controller' => 'warehouse',
        'action' => [
            'POST' => 'store_export_pingback',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'google_drive_batch',
    'integrations/google-drive/batch',
    [
        'controller' => 'google_drive',
        'action' => [
            'POST' => 'batch_add',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);
$this->map(
    'dropbox_batch',
    'integrations/dropbox/batch',
    [
        'controller' => 'dropbox',
        'action' => [
            'POST' => 'batch_add',
        ],
        'module' => EnvironmentFramework::INJECT_INTO,
    ]
);

$this->map(
    'since_last_visit',
    'since-last-visit/:parent_type/:parent_id',
    [
        'action' => [
            'GET' => 'index',
        ],
        'controller' => 'since_last_visit',
        'module' => EnvironmentFramework::INJECT_INTO,
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
