<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Compile;

use ActiveCollab\Foundation\Urls\Router\UrlAssembler\UrlAssembler;

class CompiledUrlAssembler extends UrlAssembler
{
    protected function getRouteAssemblyParts(string $route_name): array
    {
        switch ($route_name) {
            case 'api_info':
                return [
                    'info',
                    [
                        'controller' => 'utilities',
                        'action' => [
                            'GET' => 'info',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'initial':
                return [
                    'initial',
                    [
                        'controller' => 'initial',
                        'action' => 'index',
                        'module' => 'system',
                    ],
                ];
            case 'initial_speed_test':
                return [
                    'initial/test-speed',
                    [
                        'controller' => 'initial',
                        'action' => 'test_action_speed',
                        'module' => 'system',
                    ],
                ];
            case 'system_status':
                return [
                    'system-status',
                    [
                        'controller' => 'system_status',
                        'action' => 'index',
                        'module' => 'system',
                    ],
                ];
            case 'check_for_updates':
                return [
                    'system-status/check-for-updates',
                    [
                        'controller' => 'system_status',
                        'action' => 'check_for_updates',
                        'module' => 'system',
                    ],
                ];
            case 'download_release':
                return [
                    'system-status/download-release',
                    [
                        'controller' => 'system_status',
                        'action' => [
                            'GET' => 'get_download_progress',
                            'POST' => 'start_download',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'check_environment':
                return [
                    'system-status/check-environment',
                    [
                        'controller' => 'system_status',
                        'action' => 'check_environment',
                        'module' => 'system',
                    ],
                ];
            case 'upgrade':
                return [
                    'upgrade',
                    [
                        'controller' => 'upgrade',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'finish',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'upgrade_release_notes':
                return [
                    'upgrade/release-notes',
                    [
                        'controller' => 'upgrade',
                        'action' => 'release_notes',
                        'module' => 'system',
                    ],
                ];
            case 'system_notifications_dismiss':
                return [
                    '/system-notifications/:notification_id/dismiss',
                    [
                        'controller' => 'system_notifications',
                        'action' => [
                            'GET' => 'dismiss',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'trash':
                return [
                    'trash',
                    [
                        'action' => [
                            'GET' => 'show_content',
                            'DELETE' => 'empty_trash',
                        ],
                        'controller' => 'trash',
                        'module' => 'system',
                    ],
                ];
            case 'move_to_archive':
                return [
                    'move-to-archive/:parent_type/:parent_id',
                    [
                        'action' => [
                            'PUT' => 'archive',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                ];
            case 'restore_from_archive':
                return [
                    'restore-from-archive/:parent_type/:parent_id',
                    [
                        'action' => [
                            'PUT' => 'restore_from_archive',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                ];
            case 'move_to_trash':
                return [
                    'move-to-trash/:parent_type/:parent_id',
                    [
                        'action' => [
                            'PUT' => 'trash',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                ];
            case 'restore_from_trash':
                return [
                    'restore-from-trash/:parent_type/:parent_id',
                    [
                        'action' => [
                            'PUT' => 'restore_from_trash',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                ];
            case 'permanently_delete':
                return [
                    'permanently-delete/:parent_type/:parent_id',
                    [
                        'action' => [
                            'DELETE' => 'permanently_delete',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                ];
            case 'reactivate':
                return [
                    'reactivate/:parent_type/:parent_id',
                    [
                        'action' => [
                            'PUT' => 'reactivate',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                ];
            case 'complete':
                return [
                    'complete/:parent_type/:parent_id',
                    [
                        'action' => [
                            'PUT' => 'complete',
                        ],
                        'controller' => 'complete',
                        'module' => 'system',
                    ],
                ];
            case 'open':
                return [
                    'open/:parent_type/:parent_id',
                    [
                        'action' => [
                            'PUT' => 'open',
                        ],
                        'controller' => 'complete',
                        'module' => 'system',
                    ],
                ];
            case 'compare_text':
                return [
                    'compare-text',
                    [
                        'action' => [
                            'POST' => 'compare',
                        ],
                        'controller' => 'compare_text',
                        'module' => 'system',
                    ],
                ];
            case 'access_logs':
                return [
                    'access-logs/:parent_type/:parent_id',
                    [
                        'action' => [
                            'GET' => 'index',
                            'PUT' => 'log_access',
                        ],
                        'controller' => 'access_logs',
                        'module' => 'system',
                    ],
                ];
            case 'upload_files':
                return [
                    'upload-files',
                    [
                        'action' => [
                            'POST' => 'index',
                            'GET' => 'prepare',
                        ],
                        'controller' => 'upload_files',
                        'module' => 'system',
                    ],
                ];
            case 'integration_singletons':
                return [
                    'integrations/:integration_type',
                    [
                        'module' => 'system',
                        'controller' => 'integration_singletons',
                        'action' => [
                            'GET' => 'get',
                            'PUT' => 'set',
                            'DELETE' => 'forget',
                        ],
                    ],
                ];
            case 'integrations':
                return [
                    'integrations',
                    [
                        'module' => 'system',
                        'controller' => 'integrations',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'integration':
                return [
                    'integrations/:integration_id',
                    [
                        'module' => 'system',
                        'controller' => 'integrations',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'cron_integration':
                return [
                    'integrations/cron',
                    [
                        'module' => 'system',
                        'controller' => 'cron_integration',
                        'action' => 'get',
                        'integration_type' => 'cron',
                    ],
                ];
            case 'search':
                return [
                    'search',
                    [
                        'action' => [
                            'GET' => 'query',
                        ],
                        'controller' => 'search',
                        'module' => 'system',
                    ],
                ];
            case 'search_integration_configure':
                return [
                    'integrations/search/configure',
                    [
                        'module' => 'system',
                        'controller' => 'search_integration',
                        'action' => [
                            'PUT' => 'configure',
                        ],
                        'integration_type' => 'search',
                    ],
                ];
            case 'search_integration_test_connection':
                return [
                    'integrations/search/test-connection',
                    [
                        'module' => 'system',
                        'controller' => 'search_integration',
                        'action' => [
                            'POST' => 'test_connection',
                        ],
                        'integration_type' => 'search',
                    ],
                ];
            case 'search_integration_disconnect':
                return [
                    'integrations/search/disconnect',
                    [
                        'module' => 'system',
                        'controller' => 'search_integration',
                        'action' => [
                            'POST' => 'disconnect',
                        ],
                        'integration_type' => 'search',
                    ],
                ];
            case 'languages':
                return [
                    'languages',
                    [
                        'module' => 'system',
                        'controller' => 'languages',
                        'action' => [
                            'GET' => 'index',
                        ],
                    ],
                ];
            case 'language':
                return [
                    'languages/:language_id',
                    [
                        'module' => 'system',
                        'controller' => 'languages',
                        'action' => [
                            'GET' => 'view',
                        ],
                    ],
                ];
            case 'languages_default':
                return [
                    'languages/default',
                    [
                        'controller' => 'languages',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'localization':
                return [
                    'localization',
                    [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'localization_timezones':
                return [
                    'localization/timezones',
                    [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_timezones',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'localization_date_formats':
                return [
                    'localization/date-formats',
                    [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_date_formats',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'localization_time_formats':
                return [
                    'localization/time-formats',
                    [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_time_formats',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'localization_countries':
                return [
                    'localization/countries',
                    [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_countries',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'localization_eu_countries':
                return [
                    'localization/eu-countries',
                    [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_eu_countries',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'localization_states':
                return [
                    'localization/states',
                    [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_states',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'config_options':
                return [
                    'config-options',
                    [
                        'action' => [
                            'GET' => 'get',
                            'PUT' => 'set',
                        ],
                        'controller' => 'config_options',
                        'module' => 'system',
                    ],
                ];
            case 'personalized_config_options':
                return [
                    'personalized-config-options',
                    [
                        'action' => [
                            'GET' => 'personalized_get',
                            'PUT' => 'personalized_set',
                        ],
                        'controller' => 'config_options',
                        'module' => 'system',
                    ],
                ];
            case 'currencies':
                return [
                    'currencies',
                    [
                        'module' => 'system',
                        'controller' => 'currencies',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'currency':
                return [
                    'currencies/:currency_id',
                    [
                        'module' => 'system',
                        'controller' => 'currencies',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'currencies_default':
                return [
                    'currencies/default',
                    [
                        'controller' => 'currencies',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'workweek':
                return [
                    'workweek',
                    [
                        'controller' => 'workweek',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'reports':
                return [
                    'reports',
                    [
                        'controller' => 'reports',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'reports_run':
                return [
                    'reports/run',
                    [
                        'controller' => 'reports',
                        'action' => [
                            'GET' => 'run',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'reports_export':
                return [
                    'reports/export',
                    [
                        'controller' => 'reports',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'day_offs':
                return [
                    'day-offs',
                    [
                        'module' => 'system',
                        'controller' => 'day_offs',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'day_off':
                return [
                    'day-offs/:day_off_id',
                    [
                        'module' => 'system',
                        'controller' => 'day_offs',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'data_filters':
                return [
                    'data-filters',
                    [
                        'module' => 'system',
                        'controller' => 'data_filters',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'data_filter':
                return [
                    'data-filters/:data_filter_id',
                    [
                        'module' => 'system',
                        'controller' => 'data_filters',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'favorites':
                return [
                    'favorites',
                    [
                        'action' => [
                            'GET' => 'index',
                        ],
                        'controller' => 'favorites',
                        'module' => 'system',
                    ],
                ];
            case 'favorite':
                return [
                    'favorites/:parent_type/:parent_id',
                    [
                        'action' => [
                            'GET' => 'check',
                            'PUT' => 'add',
                            'DELETE' => 'remove',
                        ],
                        'controller' => 'favorites',
                        'module' => 'system',
                    ],
                ];
            case 'warehouse_pingback':
                return [
                    'integrations/warehouse/pingback',
                    [
                        'controller' => 'warehouse',
                        'action' => [
                            'POST' => 'pingback',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'warehouse_store_export_complete_pingback':
                return [
                    'integrations/warehouse/store/export',
                    [
                        'controller' => 'warehouse',
                        'action' => [
                            'POST' => 'store_export_pingback',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'google_drive_batch':
                return [
                    'integrations/google-drive/batch',
                    [
                        'controller' => 'google_drive',
                        'action' => [
                            'POST' => 'batch_add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'dropbox_batch':
                return [
                    'integrations/dropbox/batch',
                    [
                        'controller' => 'dropbox',
                        'action' => [
                            'POST' => 'batch_add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'since_last_visit':
                return [
                    'since-last-visit/:parent_type/:parent_id',
                    [
                        'action' => [
                            'GET' => 'index',
                        ],
                        'controller' => 'since_last_visit',
                        'module' => 'system',
                    ],
                ];
            case 'history':
                return [
                    'history/:parent_type/:parent_id',
                    [
                        'action' => [
                            'GET' => 'index',
                        ],
                        'controller' => 'history',
                        'module' => 'system',
                    ],
                ];
            case 'email_integration_email_log':
                return [
                    'integrations/email/email-log',
                    [
                        'module' => 'system',
                        'controller' => 'email_integration',
                        'action' => [
                            'GET' => 'email_log',
                        ],
                        'integration_type' => 'email',
                    ],
                ];
            case 'email_integration_test_connection':
                return [
                    'integrations/email/test-connection',
                    [
                        'module' => 'system',
                        'controller' => 'email_integration',
                        'action' => [
                            'POST' => 'test_connection',
                        ],
                        'integration_type' => 'email',
                    ],
                ];
            case 'attachments':
                return [
                    'attachments',
                    [
                        'module' => 'system',
                        'controller' => 'attachments',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'attachment':
                return [
                    'attachments/:attachment_id',
                    [
                        'module' => 'system',
                        'controller' => 'attachments',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'attachment_download':
                return [
                    'attachments/:attachment_id/download',
                    [
                        'controller' => 'attachments',
                        'action' => [
                            'GET' => 'download',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'attachments_batch_download':
                return [
                    'attachments/:parent_type/:parent_id/download',
                    [
                        'controller' => 'attachments_archive',
                        'action' => [
                            'POST' => 'prepare',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'notifications':
                return [
                    'notifications',
                    [
                        'module' => 'system',
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'notification':
                return [
                    'notifications/:notification_id',
                    [
                        'module' => 'system',
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'notifications_unread':
                return [
                    'notifications/unread',
                    [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'unread',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'notifications_object_updates':
                return [
                    'notifications/object-updates',
                    [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'object_updates',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'notifications_object_objets_notification':
                return [
                    'notifications/object-updates/:notification_id',
                    [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'notification_object_updates',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'notifications_object_updates_unread_count':
                return [
                    'notifications/object-updates/unread-count',
                    [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'object_updates_unread_count',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'notifications_recent_object_updates':
                return [
                    'notifications/object-updates/recent',
                    [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'recent_object_updates',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'notifications_mark_all_as_read':
                return [
                    'notifications/mark-all-as-read',
                    [
                        'controller' => 'notifications',
                        'action' => [
                            'PUT' => 'mark_all_as_read',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'push_notification_subscribe_actions':
                return [
                    'notifications/push',
                    [
                        'controller' => 'notifications',
                        'action' => [
                            'POST' => 'push_notification_subscribe',
                            'PUT' => 'push_notification_unsubscribe',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'public_notifications_subscribe':
                return [
                    'public/notifications/subscribe',
                    [
                        'controller' => 'public_notifications',
                        'action' => 'subscribe',
                        'module' => 'system',
                    ],
                ];
            case 'public_notifications_unsubscribe':
                return [
                    'public/notifications/unsubscribe',
                    [
                        'controller' => 'public_notifications',
                        'action' => 'unsubscribe',
                        'module' => 'system',
                    ],
                ];
            case 'subscribers':
                return [
                    'subscribers/:parent_type/:parent_id',
                    [
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'bulk_subscribe',
                            'PUT' => 'bulk_update',
                            'DELETE' => 'bulk_unsubscribe',
                        ],
                        'controller' => 'subscribers',
                        'module' => 'system',
                    ],
                ];
            case 'subscriber':
                return [
                    'subscribers/:parent_type/:parent_id/users/:user_id',
                    [
                        'action' => [
                            'POST' => 'subscribe',
                            'DELETE' => 'unsubscribe',
                        ],
                        'controller' => 'subscribers',
                        'module' => 'system',
                    ],
                ];
            case 'labels':
                return [
                    'labels',
                    [
                        'module' => 'system',
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'label':
                return [
                    'labels/:label_id',
                    [
                        'module' => 'system',
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'labels_reorder':
                return [
                    'labels/reorder',
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'labels',
                        'module' => 'system',
                    ],
                ];
            case 'label_set_as_default':
                return [
                    'labels/:label_id/set-as-default',
                    [
                        'action' => [
                            'PUT' => 'set_as_default',
                        ],
                        'controller' => 'labels',
                        'module' => 'system',
                    ],
                ];
            case 'payments':
                return [
                    'payments',
                    [
                        'module' => 'system',
                        'controller' => 'payments',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'payment':
                return [
                    'payments/:payment_id',
                    [
                        'module' => 'system',
                        'controller' => 'payments',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'public_payments':
                return [
                    'public_payments',
                    [
                        'controller' => 'public_payments',
                        'action' => [
                            'GET' => 'view',
                            'POST' => 'add',
                            'PUT' => 'update',
                            'DELETE' => 'cancel',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'public_payment_authorizenet_confirm':
                return [
                    'public_payments/authorizenet-confirm',
                    [
                        'controller' => 'public_payments',
                        'action' => [
                            'GET' => 'authorizenet_confirm',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'public_payment_authorizenet_form':
                return [
                    'public_payments/authorizenet-form',
                    [
                        'controller' => 'public_payments',
                        'action' => [
                            'GET' => 'authorizenet_form',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'payment_gateways':
                return [
                    'payment-gateways',
                    [
                        'controller' => 'payment_gateways',
                        'action' => [
                            'GET' => 'get_settings',
                            'PUT' => 'update_settings',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'payment_gateway_clear_paypal':
                return [
                    'payment-gateways/clear-paypal',
                    [
                        'controller' => 'payment_gateways',
                        'action' => [
                            'DELETE' => 'clear_paypal',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'payment_gateway_clear_credit_card':
                return [
                    'payment-gateways/clear-credit-card',
                    [
                        'controller' => 'payment_gateways',
                        'action' => [
                            'DELETE' => 'clear_credit_card',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'payment_process_credit_card':
                return [
                    'public-payments/process-stripe-confirmation-intent',
                    [
                        'controller' => 'public_payments',
                        'action' => [
                            'POST' => 'process_stripe_confirmation_intent',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'reminders':
                return [
                    'reminders/:parent_type/:parent_id',
                    [
                        'module' => 'system',
                        'controller' => 'reminders',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'reminder':
                return [
                    'reminders/:reminder_id',
                    [
                        'module' => 'system',
                        'controller' => 'reminders',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'calendars':
                return [
                    'calendars',
                    [
                        'module' => 'system',
                        'controller' => 'calendars',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'calendar':
                return [
                    'calendars/:calendar_id',
                    [
                        'module' => 'system',
                        'controller' => 'calendars',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'calendars_events':
                return [
                    'calendars/events',
                    [
                        'controller' => 'calendars',
                        'action' => [
                            'GET' => 'all_calendar_events',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'calendar_events':
                return [
                    'calendars/:calendar_id/events',
                    [
                        'module' => 'system',
                        'controller' => 'calendar_events',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'calendar_event':
                return [
                    'calendars/:calendar_id/events/:calendar_event_id',
                    [
                        'module' => 'system',
                        'controller' => 'calendar_events',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'users':
                return [
                    'users',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'user':
                return [
                    'users/:user_id',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'users_invite':
                return [
                    'users/invite',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'POST' => 'invite',
                        ],
                    ],
                ];
            case 'users_all':
                return [
                    'users/all',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'all',
                        ],
                    ],
                ];
            case 'users_archive':
                return [
                    'users/archive',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'archive',
                        ],
                    ],
                ];
            case 'users_check_email':
                return [
                    'users/check-email',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'check_email',
                        ],
                    ],
                ];
            case 'user_archive':
                return [
                    'users/:user_id/archive',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'move_to_archive',
                        ],
                    ],
                ];
            case 'user_reactivate':
                return [
                    'users/:user_id/reactivate',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'reactivate',
                        ],
                    ],
                ];
            case 'user_change_password':
                return [
                    'users/:user_id/change-password',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_password',
                        ],
                    ],
                ];
            case 'user_change_user_password':
                return [
                    'users/:user_id/change-user-password',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_user_password',
                        ],
                    ],
                ];
            case 'user_change_user_profile':
                return [
                    'users/:user_id/change-user-profile',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_user_profile',
                        ],
                    ],
                ];
            case 'user_resend_invitation':
                return [
                    'users/:user_id/resend-invitation',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'resend_invitation',
                        ],
                    ],
                ];
            case 'user_get_invitation':
                return [
                    'users/:user_id/get-invitation',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'get_invitation',
                        ],
                    ],
                ];
            case 'user_get_accept_invitation_url':
                return [
                    'users/:user_id/get-invitation/accept-url',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'get_accept_invitation_url',
                        ],
                    ],
                ];
            case 'user_export':
                return [
                    'users/:user_id/export',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'export',
                        ],
                    ],
                ];
            case 'user_activities':
                return [
                    'users/:user_id/activities',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'activities',
                        ],
                    ],
                ];
            case 'user_clear_avatar':
                return [
                    'users/:user_id/clear-avatar',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'DELETE' => 'clear_avatar',
                        ],
                    ],
                ];
            case 'user_change_role':
                return [
                    'users/:user_id/change-role',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_role',
                        ],
                    ],
                ];
            case 'user_change_daily_capacity':
                return [
                    'users/:user_id/change-daily-capacity',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_daily_capacity',
                        ],
                    ],
                ];
            case 'user_profile_permissions':
                return [
                    'users/:user_id/profile-permissions',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'profile_permissions',
                        ],
                    ],
                ];
            case 'user_password_permissions':
                return [
                    'users/:user_id/password-permissions',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'password_permissions',
                        ],
                    ],
                ];
            case 'user_job_types':
                return [
                    'users/:user_id/job-types',
                    [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'job_types',
                        ],
                    ],
                ];
            case 'user_sessions':
                return [
                    'users/:user_id/sessions',
                    [
                        'module' => 'system',
                        'controller' => 'user_sessions',
                        'action' => [
                            'GET' => 'index',
                            'DELETE' => 'remove',
                        ],
                    ],
                ];
            case 'api_subscriptions':
                return [
                    'users/:user_id/api-subscriptions',
                    [
                        'module' => 'system',
                        'controller' => 'api_subscriptions',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'api_subscription':
                return [
                    'users/:user_id/api-subscriptions/:api_subscription_id',
                    [
                        'module' => 'system',
                        'controller' => 'api_subscriptions',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'user_workspaces':
                return [
                    'users/:user_id/workspaces',
                    [
                        'module' => 'system',
                        'controller' => 'user_workspaces',
                        'action' => [
                            'GET' => 'index',
                        ],
                    ],
                ];
            case 'user_session':
                return [
                    'user-session',
                    [
                        'controller' => 'user_session',
                        'action' => [
                            'GET' => 'who_am_i',
                            'POST' => 'login',
                            'DELETE' => 'logout',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'authorize_file_access':
                return [
                    'authorize-file-access',
                    [
                        'controller' => 'authorize_file_access',
                        'action' => [
                            'POST' => 'authorize',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'issue_file_access_token':
                return [
                    'issue-file-access-token',
                    [
                        'controller' => 'user_session',
                        'action' => [
                            'GET' => 'issue_file_access_token',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'issue_token':
                return [
                    'issue-token',
                    [
                        'controller' => 'user_session',
                        'action' => [
                            'POST' => 'issue_token',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'issue_token_by_intent':
                return [
                    'issue-token-intent',
                    [
                        'controller' => 'user_session',
                        'action' => [
                            'POST' => 'issue_token',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'accept_invitation':
                return [
                    'accept-invitation',
                    [
                        'controller' => 'user_session',
                        'action' => [
                            'GET' => 'view_invitation',
                            'POST' => 'accept_invitation',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'password_recovery_send_code':
                return [
                    'password-recovery/send-code',
                    [
                        'controller' => 'password_recovery',
                        'action' => [
                            'POST' => 'send_code',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'password_recovery_reset_password':
                return [
                    'password-recovery/reset-password',
                    [
                        'controller' => 'password_recovery',
                        'action' => [
                            'POST' => 'reset_password',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'socket_auth':
                return [
                    'socket/auth',
                    [
                        'controller' => 'socket_auth',
                        'action' => [
                            'POST' => 'authenticate',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'companies':
                return [
                    'companies',
                    [
                        'module' => 'system',
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'company':
                return [
                    'companies/:company_id',
                    [
                        'module' => 'system',
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'companies_all':
                return [
                    'companies/all',
                    [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'all',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'companies_archive':
                return [
                    'companies/archive',
                    [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'companies_notes':
                return [
                    'companies/notes',
                    [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'notes',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'company_export':
                return [
                    'companies/:company_id/export',
                    [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'company_projects':
                return [
                    'companies/:company_id/projects',
                    [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'projects',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'company_project_names':
                return [
                    'companies/:company_id/project-names',
                    [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'project_names',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'company_invoices':
                return [
                    'companies/:company_id/invoices',
                    [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'invoices',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'teams':
                return [
                    'teams',
                    [
                        'module' => 'system',
                        'controller' => 'teams',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'team':
                return [
                    'teams/:team_id',
                    [
                        'module' => 'system',
                        'controller' => 'teams',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'team_members':
                return [
                    'teams/:team_id/members',
                    [
                        'controller' => 'team_members',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'team_member':
                return [
                    'teams/:team_id/members/:user_id',
                    [
                        'controller' => 'team_members',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'categories':
                return [
                    'categories',
                    [
                        'module' => 'system',
                        'controller' => 'categories',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'category':
                return [
                    'categories/:category_id',
                    [
                        'module' => 'system',
                        'controller' => 'categories',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'projects':
                return [
                    'projects',
                    [
                        'module' => 'system',
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'project':
                return [
                    'projects/:project_id',
                    [
                        'module' => 'system',
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'projects_filter':
                return [
                    'projects/filter',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'filter',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_archive':
                return [
                    'projects/archive',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_archive_filters':
                return [
                    'projects/archive-filters',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'filters_for_archived',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_names':
                return [
                    'projects/names',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'names',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_for_screen':
                return [
                    'projects/for-screen',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'for_screen',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_with_tracking_enabled':
                return [
                    'projects/with-tracking-enabled',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'with_tracking_enabled',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_labels':
                return [
                    'projects/labels',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'labels',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_calendar_events':
                return [
                    'projects/calendar-events',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'calendar_events',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_categories':
                return [
                    'projects/categories',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'categories',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_financial_stats':
                return [
                    'projects/:project_id/financial-stats',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'financial_stats',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_budgeting_data':
                return [
                    'projects/budgeting-data',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'budgeting_data',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_batch_update_budget_types':
                return [
                    'projects/batch-update-budget-types',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'PUT' => 'batch_update_budget_types',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_whats_new':
                return [
                    'projects/:project_id/whats-new',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'whats_new',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_budget':
                return [
                    'projects/:project_id/budget',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'budget',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_export':
                return [
                    'projects/:project_id/export',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_additional_data':
                return [
                    'projects/:project_id/additional-data',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'additional_data',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_members':
                return [
                    'projects/:project_id/members',
                    [
                        'controller' => 'project_members',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_member':
                return [
                    'projects/:project_id/members/:user_id',
                    [
                        'controller' => 'project_members',
                        'action' => [
                            'PUT' => 'replace',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_revoke_client_access':
                return [
                    'projects/:project_id/revoke-client-access',
                    [
                        'controller' => 'project_members',
                        'action' => [
                            'PUT' => 'revoke_client_access',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_responsibilities':
                return [
                    'projects/:project_id/responsibilities',
                    [
                        'controller' => 'project_members',
                        'action' => [
                            'GET' => 'responsibilities',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_applied_templates':
                return [
                    'projects/:project_id/applied-templates',
                    [
                        'controller' => 'applied_templates',
                        'action' => [
                            'GET' => 'applied_templates',
                            'POST' => 'apply_template',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'task_lists':
                return [
                    'projects/:project_id/task-lists',
                    [
                        'module' => 'tasks',
                        'controller' => 'task_lists',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'task_list':
                return [
                    'projects/:project_id/task-lists/:task_list_id',
                    [
                        'module' => 'tasks',
                        'controller' => 'task_lists',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'task_lists_all_task_lists':
                return [
                    'projects/:project_id/task-lists/all',
                    [
                        'action' => [
                            'GET' => 'all_task_lists',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_lists_archive':
                return [
                    'projects/:project_id/task-lists/archive',
                    [
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_lists_reorder':
                return [
                    'projects/:project_id/task-lists/reorder',
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_list_move_to_project':
                return [
                    'projects/:project_id/task-lists/:task_list_id/move-to-project',
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_list_duplicate':
                return [
                    'projects/:project_id/task-lists/:task_list_id/duplicate',
                    [
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_list_open_tasks':
                return [
                    'projects/:project_id/task-lists/:task_list_id/tasks',
                    [
                        'action' => [
                            'GET' => 'open_tasks',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_list_completed_tasks':
                return [
                    'projects/:project_id/task-lists/:task_list_id/completed-tasks',
                    [
                        'action' => [
                            'GET' => 'completed_tasks',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_lists_insert':
                return [
                    'projects/:project_id/task-lists/insert',
                    [
                        'action' => [
                            'POST' => 'insert',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                ];
            case 'tasks':
                return [
                    'projects/:project_id/tasks',
                    [
                        'module' => 'tasks',
                        'controller' => 'tasks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                            'PUT' => 'batch_update',
                        ],
                    ],
                ];
            case 'task':
                return [
                    'projects/:project_id/tasks/:task_id',
                    [
                        'module' => 'tasks',
                        'controller' => 'tasks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'tasks_archive':
                return [
                    'projects/:project_id/tasks/archive',
                    [
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'tasks_reorder':
                return [
                    'projects/:project_id/tasks/reorder',
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'tasks_for_screen':
                return [
                    'projects/:project_id/tasks/for-screen',
                    [
                        'action' => [
                            'GET' => 'for_screen',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_time_records':
                return [
                    'projects/:project_id/tasks/:task_id/time-records',
                    [
                        'action' => [
                            'GET' => 'time_records',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_time_records_info':
                return [
                    'projects/:project_id/tasks/:task_id/time-records/info',
                    [
                        'action' => [
                            'GET' => 'time_records_info',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_expenses':
                return [
                    'projects/:project_id/tasks/:task_id/expenses',
                    [
                        'action' => [
                            'GET' => 'expenses',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'subtasks':
                return [
                    'projects/:project_id/tasks/:task_id/subtasks',
                    [
                        'module' => 'tasks',
                        'controller' => 'subtasks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'subtask':
                return [
                    'projects/:project_id/tasks/:task_id/subtasks/:subtask_id',
                    [
                        'module' => 'tasks',
                        'controller' => 'subtasks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'subtasks_reorder':
                return [
                    'projects/:project_id/tasks/:task_id/subtasks/reorder',
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'subtasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'subtask_promote_to_task':
                return [
                    'projects/:project_id/tasks/:task_id/subtasks/:subtask_id/promote-to-task',
                    [
                        'action' => [
                            'POST' => 'promote_to_task',
                        ],
                        'controller' => 'subtasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_move_to_project':
                return [
                    'projects/:project_id/tasks/:task_id/move-to-project',
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'task_duplicate':
                return [
                    'projects/:project_id/tasks/:task_id/duplicate',
                    [
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'recurring_tasks':
                return [
                    'projects/:project_id/recurring-tasks',
                    [
                        'module' => 'tasks',
                        'controller' => 'recurring_tasks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'recurring_task':
                return [
                    'projects/:project_id/recurring-tasks/:recurring_task_id',
                    [
                        'module' => 'tasks',
                        'controller' => 'recurring_tasks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'recurring_task_create_task':
                return [
                    'projects/:project_id/recurring-tasks/:recurring_task_id/create-task',
                    [
                        'action' => [
                            'POST' => 'create_task',
                        ],
                        'controller' => 'recurring_tasks',
                        'module' => 'tasks',
                    ],
                ];
            case 'discussions':
                return [
                    'projects/:project_id/discussions',
                    [
                        'module' => 'discussions',
                        'controller' => 'discussions',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'discussion':
                return [
                    'projects/:project_id/discussions/:discussion_id',
                    [
                        'module' => 'discussions',
                        'controller' => 'discussions',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'discussions_read_status':
                return [
                    'projects/:project_id/discussions/read-status',
                    [
                        'action' => [
                            'GET' => 'read_status',
                        ],
                        'controller' => 'discussions',
                        'module' => 'discussions',
                    ],
                ];
            case 'discussion_move_to_project':
                return [
                    'projects/:project_id/discussions/:discussion_id/move-to-project',
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'discussions',
                        'module' => 'discussions',
                    ],
                ];
            case 'discussion_promote_to_task':
                return [
                    'projects/:project_id/discussions/:discussion_id/promote-to-task',
                    [
                        'action' => [
                            'POST' => 'promote_to_task',
                        ],
                        'controller' => 'discussions',
                        'module' => 'discussions',
                    ],
                ];
            case 'files':
                return [
                    'projects/:project_id/files',
                    [
                        'module' => 'files',
                        'controller' => 'files',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'file':
                return [
                    'projects/:project_id/files/:file_id',
                    [
                        'module' => 'files',
                        'controller' => 'files',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'files_batch':
                return [
                    'projects/:project_id/files/batch',
                    [
                        'action' => [
                            'GET' => 'batch_download',
                            'POST' => 'batch_add',
                        ],
                        'controller' => 'files',
                        'module' => 'files',
                    ],
                ];
            case 'file_download':
                return [
                    'projects/:project_id/files/:file_id/download',
                    [
                        'action' => [
                            'GET' => 'download',
                        ],
                        'controller' => 'files',
                        'module' => 'files',
                    ],
                ];
            case 'file_move_to_project':
                return [
                    'projects/:project_id/files/:file_id/move-to-project',
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'files',
                        'module' => 'files',
                    ],
                ];
            case 'notes':
                return [
                    'projects/:project_id/notes',
                    [
                        'module' => 'notes',
                        'controller' => 'notes',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'note':
                return [
                    'projects/:project_id/notes/:note_id',
                    [
                        'module' => 'notes',
                        'controller' => 'notes',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'notes_reorder':
                return [
                    'projects/:project_id/notes/reorder',
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                ];
            case 'note_move_to_group':
                return [
                    'projects/:project_id/notes/:note_id/move-to-group',
                    [
                        'action' => [
                            'PUT' => 'move_to_group',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                ];
            case 'note_move_to_project':
                return [
                    'projects/:project_id/notes/:note_id/move-to-project',
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                ];
            case 'note_versions':
                return [
                    'projects/:project_id/notes/:note_id/versions',
                    [
                        'action' => [
                            'GET' => 'versions',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                ];
            case 'note_groups':
                return [
                    'projects/:project_id/note-groups',
                    [
                        'module' => 'notes',
                        'controller' => 'note_groups',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'note_group':
                return [
                    'projects/:project_id/note-groups/:note_group_id',
                    [
                        'module' => 'notes',
                        'controller' => 'note_groups',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'note_group_notes':
                return [
                    'projects/:project_id/note-groups/:note_group_id/notes',
                    [
                        'controller' => 'note_groups',
                        'action' => [
                            'GET' => 'notes',
                        ],
                        'module' => 'notes',
                    ],
                ];
            case 'note_group_reorder_notes':
                return [
                    'projects/:project_id/note-groups/:note_group_id/reorder-notes',
                    [
                        'controller' => 'note_groups',
                        'action' => [
                            'PUT' => 'reorder_notes',
                        ],
                        'module' => 'notes',
                    ],
                ];
            case 'note_group_move_to_group':
                return [
                    'projects/:project_id/note-groups/:note_group_id/move-to-group',
                    [
                        'controller' => 'note_groups',
                        'action' => [
                            'PUT' => 'move_to_group',
                        ],
                        'module' => 'notes',
                    ],
                ];
            case 'time_records':
                return [
                    'projects/:project_id/time-records',
                    [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'time_record':
                return [
                    'projects/:project_id/time-records/:time_record_id',
                    [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'time_records_filtered_by_date':
                return [
                    'projects/:project_id/time-records/filtered-by-date',
                    [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'GET' => 'filtered_by_date',
                        ],
                    ],
                ];
            case 'time_record_move':
                return [
                    'projects/:project_id/time-records/:time_record_id/move',
                    [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'PUT' => 'move',
                        ],
                    ],
                ];
            case 'expenses':
                return [
                    'projects/:project_id/expenses',
                    [
                        'module' => 'tracking',
                        'controller' => 'expenses',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'expense':
                return [
                    'projects/:project_id/expenses/:expense_id',
                    [
                        'module' => 'tracking',
                        'controller' => 'expenses',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'expense_move':
                return [
                    'projects/:project_id/expenses/:expense_id/move',
                    [
                        'module' => 'tracking',
                        'controller' => 'expenses',
                        'action' => [
                            'PUT' => 'move',
                        ],
                    ],
                ];
            case 'project_labels':
                return [
                    'labels/project-labels',
                    [
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'project_labels',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'task_labels':
                return [
                    'labels/task-labels',
                    [
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'task_labels',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_templates':
                return [
                    'project-templates',
                    [
                        'module' => 'system',
                        'controller' => 'project_templates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'project_template':
                return [
                    'project-templates/:project_template_id',
                    [
                        'module' => 'system',
                        'controller' => 'project_templates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'project_template_duplicate':
                return [
                    'project-templates/:project_template_id/duplicate',
                    [
                        'controller' => 'project_templates',
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_template_reorder':
                return [
                    'project-templates/:project_template_id/reorder',
                    [
                        'controller' => 'project_templates',
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_template_elements':
                return [
                    'project-templates/:project_template_id/elements',
                    [
                        'module' => 'system',
                        'controller' => 'project_template_elements',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'project_template_element':
                return [
                    'project-templates/:project_template_id/elements/:project_template_element_id',
                    [
                        'module' => 'system',
                        'controller' => 'project_template_elements',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'project_template_elements_batch':
                return [
                    'project-templates/:project_template_id/elements/batch',
                    [
                        'action' => [
                            'POST' => 'batch_add',
                        ],
                        'controller' => 'project_template_elements',
                        'module' => 'system',
                    ],
                ];
            case 'project_template_element_download':
                return [
                    'project-templates/:project_template_id/elements/download',
                    [
                        'action' => [
                            'GET' => 'download',
                        ],
                        'controller' => 'project_template_elements',
                        'module' => 'system',
                    ],
                ];
            case 'project_template_elements_reorder':
                return [
                    'project-templates/:project_template_id/elements/reorder',
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'project_template_elements',
                        'module' => 'system',
                    ],
                ];
            case 'project_template_task_dependencies':
                return [
                    'project-templates/dependencies/tasks/:task_id',
                    [
                        'controller' => 'project_template_task_dependencies',
                        'action' => [
                            'GET' => 'view',
                            'POST' => 'create',
                            'PUT' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'project_template_task_dependencies_suggestions':
                return [
                    'project-templates/dependencies/tasks/:task_id/suggestions',
                    [
                        'controller' => 'project_template_task_dependencies',
                        'action' => [
                            'GET' => 'dependency_suggestions',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'projects_with_people_permissions':
                return [
                    '/projects/with-people-permissions',
                    [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'with_people_permissions',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'user_projects':
                return [
                    'users/:user_id/projects',
                    [
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'projects',
                            'POST' => 'add_to_projects',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'user_project_ids':
                return [
                    'users/:user_id/projects/ids',
                    [
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'project_ids',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'feedback':
                return [
                    'feedback',
                    [
                        'controller' => 'feedback',
                        'action' => [
                            'POST' => 'send',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'feedback_check':
                return [
                    'feedback/check',
                    [
                        'controller' => 'feedback',
                        'action' => [
                            'GET' => 'check',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'new_features':
                return [
                    'new-features',
                    [
                        'controller' => 'new_features',
                        'action' => [
                            'GET' => 'list_new_features',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'maintenance_mode':
                return [
                    'maintenance-mode',
                    [
                        'controller' => 'maintenance_mode',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'security':
                return [
                    'security',
                    [
                        'controller' => 'security',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'versions':
                return [
                    'system/versions/old-versions',
                    [
                        'controller' => 'versions',
                        'action' => [
                            'GET' => 'check_old_versions',
                            'DELETE' => 'delete_old_versions',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'basecamp_check_credentials':
                return [
                    'integrations/basecamp-importer/check-credentials',
                    [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'check_credentials',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                ];
            case 'basecamp_schedule_import':
                return [
                    'integrations/basecamp-importer/schedule-import',
                    [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                ];
            case 'basecamp_start_over':
                return [
                    'integrations/basecamp-importer/start-over',
                    [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                ];
            case 'basecamp_check_status':
                return [
                    'integrations/basecamp-importer/check-status',
                    [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                ];
            case 'basecamp_invite_users':
                return [
                    'integrations/basecamp-importer/invite-users',
                    [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'invite_users',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                ];
            case 'client_plus':
                return [
                    'integrations/client-plus',
                    [
                        'controller' => 'client_plus_integration',
                        'action' => [
                            'POST' => 'activate',
                            'DELETE' => 'deactivate',
                        ],
                        'integration_type' => 'client_plus',
                        'module' => 'system',
                    ],
                ];
            case 'trello_request_url':
                return [
                    'integrations/trello-importer/request-url',
                    [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'GET' => 'get_request_url',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                ];
            case 'trello_authorize':
                return [
                    'integrations/trello-importer/authorize',
                    [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                ];
            case 'trello_schedule_import':
                return [
                    'integrations/trello-importer/schedule-import',
                    [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                ];
            case 'trello_start_over':
                return [
                    'integrations/trello-importer/start-over',
                    [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                ];
            case 'trello_check_status':
                return [
                    'integrations/trello-importer/check-status',
                    [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                ];
            case 'trello_invite_users':
                return [
                    'integrations/trello-importer/invite-users',
                    [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'GET' => 'invite_users',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                ];
            case 'asana_request_url':
                return [
                    'integrations/asana-importer/request-url',
                    [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'GET' => 'get_request_url',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                ];
            case 'asana_authorize':
                return [
                    'integrations/asana-importer/authorize',
                    [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                ];
            case 'asana_schedule_import':
                return [
                    'integrations/asana-importer/schedule-import',
                    [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                ];
            case 'asana_start_over':
                return [
                    'integrations/asana-importer/start-over',
                    [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                ];
            case 'asana_check_status':
                return [
                    'integrations/asana-importer/check-status',
                    [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                ];
            case 'asana_invite_users':
                return [
                    'integrations/asana-importer/invite-users',
                    [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'GET' => 'invite_users',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                ];
            case 'sample_projects':
                return [
                    'integrations/sample-projects',
                    [
                        'controller' => 'sample_projects_integration',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'import',
                        ],
                        'integration_type' => 'sample-projects',
                        'module' => 'system',
                    ],
                ];
            case 'slack_connect':
                return [
                    'integrations/slack/connect',
                    [
                        'controller' => 'slack_integration',
                        'action' => [
                            'PUT' => 'connect',
                        ],
                        'integration_type' => 'slack',
                        'module' => 'system',
                    ],
                ];
            case 'slack_notification_channel':
                return [
                    'integrations/slack/notification-channels/:notification_channel_id',
                    [
                        'controller' => 'slack_integration',
                        'action' => [
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'integration_type' => 'slack',
                        'notification_channel_id' => '\\d+',
                        'module' => 'system',
                    ],
                ];
            case 'webhooks_integration_ids':
                return [
                    'integrations/webhooks/:webhook_id',
                    [
                        'controller' => 'webhooks_integration',
                        'action' => [
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'integration_type' => 'webhooks',
                        'webhook_id' => '\\d+',
                        'module' => 'system',
                    ],
                ];
            case 'webhooks_integration':
                return [
                    'integrations/webhooks',
                    [
                        'controller' => 'webhooks_integration',
                        'action' => [
                            'POST' => 'add',
                        ],
                        'integration_type' => 'webhooks',
                        'module' => 'system',
                    ],
                ];
            case 'calendar_feeds':
                return [
                    'calendar-feeds',
                    [
                        'controller' => 'calendar_feeds',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'calendar_feeds_project':
                return [
                    'calendar-feeds/projects/:project_id',
                    [
                        'controller' => 'calendar_feeds',
                        'action' => [
                            'GET' => 'project',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'calendar_feeds_calendar':
                return [
                    'calendar-feeds/calendars/:calendar_id',
                    [
                        'controller' => 'calendar_feeds',
                        'action' => [
                            'GET' => 'calendar',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'zapier_integration':
                return [
                    'integrations/zapier',
                    [
                        'controller' => 'zapier_integration',
                        'action' => [
                            'GET' => 'get_data',
                            'POST' => 'enable',
                            'DELETE' => 'disable',
                        ],
                        'integration_type' => 'zapier',
                        'module' => 'system',
                    ],
                ];
            case 'zapier_integration_payload_example':
                return [
                    'integrations/zapier/payload-examples/:event_type',
                    [
                        'controller' => 'zapier_integration',
                        'action' => [
                            'GET' => 'payload_example',
                        ],
                        'integration_type' => 'zapier',
                        'module' => 'system',
                    ],
                ];
            case 'zapier_webhooks':
                return [
                    '/integrations/zapier/webhooks',
                    [
                        'module' => 'system',
                        'controller' => 'zapier_webhooks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'zapier_webhook':
                return [
                    '/integrations/zapier/webhooks/:zapier_webhook_id',
                    [
                        'module' => 'system',
                        'controller' => 'zapier_webhooks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'one_login_credentials':
                return [
                    'integrations/one-login/credentials',
                    [
                        'controller' => 'one_login_integration',
                        'action' => [
                            'POST' => 'credentials',
                        ],
                        'integration_type' => 'one-login',
                        'module' => 'system',
                    ],
                ];
            case 'one_login_enable':
                return [
                    'integrations/one-login/enable',
                    [
                        'controller' => 'one_login_integration',
                        'action' => [
                            'GET' => 'enable',
                        ],
                        'integration_type' => 'one-login',
                        'module' => 'system',
                    ],
                ];
            case 'one_login_disable':
                return [
                    'integrations/one-login/disable',
                    [
                        'controller' => 'one_login_integration',
                        'action' => [
                            'GET' => 'disable',
                        ],
                        'integration_type' => 'one-login',
                        'module' => 'system',
                    ],
                ];
            case 'wrike_authorize':
                return [
                    'integrations/wrike-importer/authorize',
                    [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                ];
            case 'wrike_schedule_import':
                return [
                    'integrations/wrike-importer/schedule-import',
                    [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                ];
            case 'wrike_start_over':
                return [
                    'integrations/wrike-importer/start-over',
                    [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                ];
            case 'wrike_check_status':
                return [
                    'integrations/wrike-importer/check-status',
                    [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                ];
            case 'wrike_invite_users':
                return [
                    'integrations/wrike-importer/invite-users',
                    [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'GET' => 'invite_users',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                ];
            case 'cta_notifications':
                return [
                    'cta-notifications/:notification_type',
                    [
                        'controller' => 'cta_notifications',
                        'action' => [
                            'GET' => 'show',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'cta_notifications_dismiss':
                return [
                    'cta-notifications/:notification_type/dismiss',
                    [
                        'controller' => 'cta_notifications',
                        'action' => [
                            'POST' => 'dismiss',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'reactions':
                return [
                    'reactions/:parent_type/:parent_id',
                    [
                        'action' => [
                            'POST' => 'add_reaction',
                            'DELETE' => 'remove_reaction',
                        ],
                        'controller' => 'reactions',
                        'module' => 'system',
                    ],
                ];
            case 'reaction':
                return [
                    'reactions/:reaction_id',
                    [
                        'controller' => 'reactions',
                        'action' => [],
                        'module' => 'system',
                    ],
                ];
            case 'logger':
                return [
                    'logger/:log_level',
                    [
                        'controller' => 'logger',
                        'action' => [
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'comments':
                return [
                    'comments/:parent_type/:parent_id',
                    [
                        'controller' => 'comments',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'comment':
                return [
                    'comments/:comment_id',
                    [
                        'controller' => 'comments',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'find-conversation':
                return [
                    'conversations/:type/:id',
                    [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'find',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'conversations':
                return [
                    'conversations',
                    [
                        'controller' => 'conversations',
                        'action' => [
                            'POST' => 'create',
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'conversation':
                return [
                    'conversations/:conversation_id',
                    [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'view',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'group_conversation_leave':
                return [
                    'conversations/:conversation_id/leave',
                    [
                        'controller' => 'group_conversations',
                        'action' => [
                            'PUT' => 'leave',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'group_conversation_remove':
                return [
                    'conversations/:conversation_id/remove/:user_id',
                    [
                        'controller' => 'group_conversations',
                        'action' => [
                            'DELETE' => 'remove',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'group_conversation_invite':
                return [
                    'conversations/:conversation_id/invite',
                    [
                        'controller' => 'group_conversations',
                        'action' => [
                            'POST' => 'invite',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'group_conversation_rename':
                return [
                    'conversations/:conversation_id/rename',
                    [
                        'controller' => 'group_conversations',
                        'action' => [
                            'PUT' => 'rename',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'group_conversation_admins':
                return [
                    'conversations/:conversation_id/admins/:user_id',
                    [
                        'controller' => 'group_conversations',
                        'action' => [
                            'POST' => 'add_admin',
                            'DELETE' => 'remove_admin',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'conversation_messages':
                return [
                    'conversations/:conversation_id/messages',
                    [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'create',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'conversation_messages_mark_as_unread':
                return [
                    'conversations/:conversation_id/messages/:message_id/mark-as-unread',
                    [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'PUT' => 'mark_as_unread',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'conversation_messages_create_task':
                return [
                    'conversations/:conversation_id/messages/create-task',
                    [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'POST' => 'create_task',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'conversation_message':
                return [
                    'conversations/:conversation_id/messages/:message_id',
                    [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'conversation_attachments':
                return [
                    'conversations/:conversation_id/attachments',
                    [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'attachments',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'user_conversations':
                return [
                    'user-conversations',
                    [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'user_conversations',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'user_conversation':
                return [
                    'user-conversations/:conversation_id',
                    [
                        'controller' => 'user_conversations',
                        'action' => [
                            'PUT' => 'edit',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'user_shortcuts':
                return [
                    'shortcuts/:shortcut_id',
                    [
                        'controller' => 'shortcuts',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'user_shortcuts_collection':
                return [
                    'shortcuts',
                    [
                        'controller' => 'shortcuts',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'create',
                            'DELETE' => 'batch_delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'reorder_shortcuts':
                return [
                    'shortcuts/reorder',
                    [
                        'controller' => 'shortcuts',
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'activity_logs':
                return [
                    'activity-logs',
                    [
                        'module' => 'system',
                        'controller' => 'activity_logs',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'activity_log':
                return [
                    'activity-logs/:activity_log_id',
                    [
                        'module' => 'system',
                        'controller' => 'activity_logs',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'global_activities':
                return [
                    'activities',
                    [
                        'controller' => 'activities',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'activities_project':
                return [
                    'activities/projects/:project_id',
                    [
                        'controller' => 'activities',
                        'action' => [
                            'GET' => 'project',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'activities_user':
                return [
                    'activities/users/:user_id',
                    [
                        'controller' => 'activities',
                        'action' => [
                            'GET' => 'user',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'whats_new':
                return [
                    'whats-new',
                    [
                        'controller' => 'whats_new',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'whats_new_daily':
                return [
                    'whats-new/daily/:day',
                    [
                        'controller' => 'whats_new',
                        'action' => [
                            'GET' => 'daily',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'workload_tasks':
                return [
                    'workload/tasks',
                    [
                        'controller' => 'workload',
                        'action' => [
                            'GET' => 'workload_tasks',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'workload_projects':
                return [
                    'workload/projects',
                    [
                        'controller' => 'workload',
                        'action' => [
                            'GET' => 'workload_projects',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'availability_types':
                return [
                    'availability-types',
                    [
                        'controller' => 'availability_types',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'availability_type':
                return [
                    'availability-types/:availability_type_id',
                    [
                        'controller' => 'availability_types',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'availability_records':
                return [
                    'availability-records/users/:user_id',
                    [
                        'controller' => 'availability_records',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'availability_record':
                return [
                    'availability-records/:availability_record_id',
                    [
                        'controller' => 'availability_records',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'all_availability_records':
                return [
                    'availability-records/all',
                    [
                        'controller' => 'availability_records',
                        'action' => [
                            'GET' => 'all',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'feature_pointers':
                return [
                    'feature-pointers',
                    [
                        'controller' => 'feature_pointers',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'feature_pointer':
                return [
                    'feature-pointers/:feature_pointer_id',
                    [
                        'controller' => 'feature_pointers',
                        'action' => [
                            'PUT' => 'dismiss',
                        ],
                        'module' => 'system',
                    ],
                ];
            case 'invoice_items':
                return [
                    'invoice-items',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoice_items',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'invoice_item':
                return [
                    'invoice-items/:invoice_item_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoice_items',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'invoices':
                return [
                    'invoices',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'invoice':
                return [
                    'invoices/:invoice_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'invoices_archive':
                return [
                    'invoices/archive',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoices_private_notes':
                return [
                    'invoices/private-notes',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'private_notes',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoices_preview_items':
                return [
                    'invoices/preview-items',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'preview_items',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoices_projects':
                return [
                    'invoices/projects',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'projects_invoicing_data',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_send':
                return [
                    'invoices/:invoice_id/send',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'PUT' => 'send',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_export':
                return [
                    'invoices/:invoice_id/export',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_duplicate':
                return [
                    'invoices/:invoice_id/duplicate',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_cancel':
                return [
                    'invoices/:invoice_id/cancel',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'PUT' => 'cancel',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_related_records':
                return [
                    'invoices/:invoice_id/related-records',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'DELETE' => 'release_related_records',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_mark_as_sent':
                return [
                    'invoices/:invoice_id/mark-as-sent',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'POST' => 'mark_as_sent',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_mark_zero_invoice_as_paid':
                return [
                    'invoices/:invoice_id/mark-zero-invoice-as-paid',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'POST' => 'mark_zero_invoice_as_paid',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_public':
                return [
                    's/invoice',
                    [
                        'controller' => 'public_invoice',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'make_payment',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'estimates':
                return [
                    'estimates',
                    [
                        'module' => 'invoicing',
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'estimate':
                return [
                    'estimates/:estimate_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'estimates_archive':
                return [
                    'estimates/archive',
                    [
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'estimates_private_notes':
                return [
                    'estimates/private-notes',
                    [
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'private_notes',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'estimate_send':
                return [
                    'estimates/:estimate_id/send',
                    [
                        'controller' => 'estimates',
                        'action' => [
                            'PUT' => 'send',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'estimate_export':
                return [
                    'estimates/:estimate_id/export',
                    [
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'estimate_duplicate':
                return [
                    'estimates/:estimate_id/duplicate',
                    [
                        'controller' => 'estimates',
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'recurring_profiles':
                return [
                    'recurring-profiles',
                    [
                        'module' => 'invoicing',
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'recurring_profile':
                return [
                    'recurring-profiles/:recurring_profile_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'recurring_profiles_archive':
                return [
                    'recurring-profiles/archive',
                    [
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'recurring_profiles_trigger':
                return [
                    'recurring-profiles/trigger',
                    [
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'POST' => 'trigger',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'recurring_profile_next_trigger_on':
                return [
                    'recurring-profiles/:recurring_profile_id/next-trigger-on',
                    [
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'next_trigger_on',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_template':
                return [
                    'invoice-template',
                    [
                        'controller' => 'invoice_template',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoices_suggest_number':
                return [
                    'invoices/suggest-number',
                    [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'suggest_number',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_note_templates':
                return [
                    'invoice-note-templates',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoice_note_templates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'invoice_note_template':
                return [
                    'invoice-note-templates/:invoice_note_template_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoice_note_templates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'invoice_note_templates_default':
                return [
                    'invoice-note-templates/default',
                    [
                        'controller' => 'invoice_note_templates',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                            'DELETE' => 'unset_default',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'invoice_item_templates':
                return [
                    'invoice-item-templates',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoice_item_templates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'invoice_item_template':
                return [
                    'invoice-item-templates/:invoice_item_template_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'invoice_item_templates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'tax_rates':
                return [
                    'tax-rates',
                    [
                        'module' => 'invoicing',
                        'controller' => 'tax_rates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'tax_rate':
                return [
                    'tax-rates/:tax_rate_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'tax_rates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'tax_rates_default':
                return [
                    'tax-rates/default',
                    [
                        'controller' => 'tax_rates',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                            'DELETE' => 'unset_default',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'company_addresses_for_invoicing':
                return [
                    'companies/addresses-for-invoicing',
                    [
                        'controller' => 'company_addresses',
                        'module' => 'invoicing',
                    ],
                ];
            case 'quickbooks_payments':
                return [
                    '/integrations/quickbooks/payments',
                    [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'sync_payments',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'quickbooks_request_url':
                return [
                    '/integrations/quickbooks/request-url',
                    [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'get_request_url',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'quickbooks_authorize':
                return [
                    '/integrations/quickbooks/authorize',
                    [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'quickbooks_integration':
                return [
                    '/integrations/quickbooks/data',
                    [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'get_data',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'quickbooks_integration_suggestion':
                return [
                    '/integrations/quickbooks/suggestion',
                    [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'get_suggested_transaction_number',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'quickbooks_invoices':
                return [
                    '/quickbooks/invoices',
                    [
                        'module' => 'invoicing',
                        'controller' => 'quickbooks_invoices',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'quickbooks_invoice':
                return [
                    '/quickbooks/invoices/:quickbooks_invoice_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'quickbooks_invoices',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'quickbooks_invoices_sync':
                return [
                    '/quickbooks/invoices/sync',
                    [
                        'controller' => 'quickbooks_invoices',
                        'action' => [
                            'PUT' => 'sync',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'xero_payments':
                return [
                    '/integrations/xero/payments',
                    [
                        'controller' => 'xero_integration',
                        'action' => [
                            'GET' => 'sync_payments',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'xero_request_url':
                return [
                    '/integrations/xero/authorization-url',
                    [
                        'controller' => 'xero_integration',
                        'action' => [
                            'GET' => 'get_authorization_url',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'xero_authorize':
                return [
                    '/integrations/xero/authorize',
                    [
                        'controller' => 'xero_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'xero_integration':
                return [
                    '/integrations/xero/data',
                    [
                        'controller' => 'xero_integration',
                        'action' => [
                            'GET' => 'get_data',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'xero_invoices':
                return [
                    '/xero/invoices',
                    [
                        'module' => 'invoicing',
                        'controller' => 'xero_invoices',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'xero_invoice':
                return [
                    '/xero/invoices/:xero_invoice_id',
                    [
                        'module' => 'invoicing',
                        'controller' => 'xero_invoices',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'xero_invoices_sync':
                return [
                    '/xero/invoices/sync',
                    [
                        'controller' => 'xero_invoices',
                        'action' => [
                            'PUT' => 'sync',
                        ],
                        'module' => 'invoicing',
                    ],
                ];
            case 'team_tasks':
                return [
                    'teams/:team_id/tasks',
                    [
                        'controller' => 'team_tasks',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tasks',
                    ],
                ];
            case 'user_tasks':
                return [
                    'users/:user_id/tasks',
                    [
                        'controller' => 'user_tasks',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tasks',
                    ],
                ];
            case 'unscheduled_task_counts':
                return [
                    'reports/unscheduled-tasks/count-by-project',
                    [
                        'controller' => 'unscheduled_tasks',
                        'action' => [
                            'GET' => 'count_by_project',
                        ],
                        'module' => 'tasks',
                    ],
                ];
            case 'task_dependencies':
                return [
                    'dependencies/tasks/:task_id',
                    [
                        'controller' => 'task_dependencies',
                        'action' => [
                            'GET' => 'view',
                            'POST' => 'create',
                            'PUT' => 'delete',
                        ],
                        'module' => 'tasks',
                    ],
                ];
            case 'project_task_dependencies':
                return [
                    'dependencies/project/:project_id',
                    [
                        'controller' => 'project_dependencies',
                        'action' => [
                            'GET' => 'view',
                        ],
                        'module' => 'tasks',
                    ],
                ];
            case 'task_dependency_suggestions':
                return [
                    'dependencies/tasks/:task_id/suggestions',
                    [
                        'controller' => 'task_dependencies',
                        'action' => [
                            'GET' => 'dependency_suggestions',
                        ],
                        'module' => 'tasks',
                    ],
                ];
            case 'task_reschedule':
                return [
                    'tasks/:task_id/reschedule',
                    [
                        'controller' => 'task_reschedule',
                        'action' => [
                            'GET' => 'reschedule_simulation',
                            'POST' => 'make_reschedule',
                        ],
                        'module' => 'tasks',
                    ],
                ];
            case 'time_records_report':
                return [
                    'time-records',
                    [
                        'controller' => 'timesheet_report',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'time_records_by_parent':
                return [
                    'time-records/:parent_type/:parent_id',
                    [
                        'controller' => 'time_records_by_parent',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'time_records_by_parent_info':
                return [
                    'time-records/:parent_type/:parent_id/info',
                    [
                        'controller' => 'time_records_by_parent',
                        'action' => [
                            'GET' => 'info',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'expenses_by_parent_info':
                return [
                    'expenses/:parent_type/:parent_id/info',
                    [
                        'controller' => 'expenses_by_parent',
                        'action' => [
                            'GET' => 'info',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'user_time_records':
                return [
                    'users/:user_id/time-records',
                    [
                        'controller' => 'user_time_records',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'user_time_records_filtered_by_date':
                return [
                    'users/:user_id/time-records/filtered-by-date',
                    [
                        'controller' => 'user_time_records',
                        'action' => [
                            'GET' => 'filtered_by_date',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'job_types':
                return [
                    'job-types',
                    [
                        'module' => 'tracking',
                        'controller' => 'job_types',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'job_type':
                return [
                    'job-types/:job_type_id',
                    [
                        'module' => 'tracking',
                        'controller' => 'job_types',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'job_types_default':
                return [
                    'job-types/default',
                    [
                        'controller' => 'job_types',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'job_types_batch_edit':
                return [
                    'job-types/edit-batch',
                    [
                        'controller' => 'job_types',
                        'action' => [
                            'PUT' => 'batch_edit',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'expense_categories':
                return [
                    'expense-categories',
                    [
                        'module' => 'tracking',
                        'controller' => 'expense_categories',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                ];
            case 'expense_category':
                return [
                    'expense-categories/:expense_category_id',
                    [
                        'module' => 'tracking',
                        'controller' => 'expense_categories',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                ];
            case 'expense_categories_default':
                return [
                    'expense-categories/default',
                    [
                        'controller' => 'expense_categories',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'expense_categories_batch_edit':
                return [
                    'expense-categories/edit-batch',
                    [
                        'controller' => 'expense_categories',
                        'action' => [
                            'PUT' => 'batch_edit',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'stopwatches_index':
                return [
                    'stopwatches',
                    [
                        'controller' => 'stopwatch',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'start',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'stopwatches_offset':
                return [
                    'stopwatches/offset',
                    [
                        'controller' => 'stopwatch',
                        'action' => [
                            'POST' => 'offset',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'stopwatches_pause':
                return [
                    'stopwatches/:id/pause/',
                    [
                        'controller' => 'stopwatch',
                        'action' => [
                            'PUT' => 'pause',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'stopwatches_resume':
                return [
                    'stopwatches/:id/resume',
                    [
                        'controller' => 'stopwatch',
                        'action' => [
                            'PUT' => 'resume',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'stopwatches_delete':
                return [
                    'stopwatches/:id',
                    [
                        'controller' => 'stopwatch',
                        'action' => [
                            'DELETE' => 'delete',
                            'PUT' => 'edit',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'internal_rate':
                return [
                    'users/:user_id/internal-rate',
                    [
                        'controller' => 'internal_rate',
                        'action' => [
                            'GET' => 'view',
                            'POST' => 'add',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'internal_rates':
                return [
                    'users/:user_id/internal-rates',
                    [
                        'controller' => 'internal_rate',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'internal_rates_all':
                return [
                    'users/internal-rates',
                    [
                        'controller' => 'internal_rate',
                        'action' => [
                            'GET' => 'all',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'delete_internal_rate':
                return [
                    'users/internal-rates/:id',
                    [
                        'controller' => 'internal_rate',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                        'module' => 'tracking',
                    ],
                ];
            case 'budget_thresholds_index':
                return [
                    'projects/:project_id/budget-thresholds',
                    [
                        'controller' => 'budget_thresholds',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'tracking',
                    ],
                ];
        }

        return [
            null,
            null,
        ];
    }
}

