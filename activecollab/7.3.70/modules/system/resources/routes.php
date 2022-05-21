<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use ActiveCollab\Module\Discussions\DiscussionsModule;
use ActiveCollab\Module\Files\FilesModule;
use ActiveCollab\Module\Notes\NotesModule;
use ActiveCollab\Module\Tasks\TasksModule;
use ActiveCollab\Module\Tracking\TrackingModule;

$this->mapResource(
    'users',
    null,
    function ($collection, $single) {
        $this->map("$collection[name]_invite", "$collection[path]/invite", ['module' => $collection['module'], 'controller' => $collection['controller'], 'action' => ['POST' => 'invite']], $collection['requirements']);
        $this->map("$collection[name]_all", "$collection[path]/all", ['module' => $collection['module'], 'controller' => $collection['controller'], 'action' => ['GET' => 'all']], $collection['requirements']);
        $this->map("$collection[name]_archive", "$collection[path]/archive", ['module' => $collection['module'], 'controller' => $collection['controller'], 'action' => ['GET' => 'archive']], $collection['requirements']);
        $this->map("$collection[name]_check_email", "$collection[path]/check-email", ['module' => $collection['module'], 'controller' => $collection['controller'], 'action' => ['GET' => 'check_email']], $collection['requirements']);

        $this->map("$single[name]_archive", "$single[path]/archive", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'move_to_archive']], $single['requirements']);
        $this->map("$single[name]_reactivate", "$single[path]/reactivate", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'reactivate']], $single['requirements']);
        $this->map("$single[name]_change_password", "$single[path]/change-password", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'change_password']], $single['requirements']);
        $this->map("$single[name]_change_user_password", "$single[path]/change-user-password", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'change_user_password']], $single['requirements']);
        $this->map("$single[name]_change_user_profile", "$single[path]/change-user-profile", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'change_user_profile']], $single['requirements']);
        $this->map("$single[name]_resend_invitation", "$single[path]/resend-invitation", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'resend_invitation']], $single['requirements']);
        $this->map("$single[name]_get_invitation", "$single[path]/get-invitation", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['GET' => 'get_invitation']], $single['requirements']);
        $this->map("$single[name]_get_accept_invitation_url", "$single[path]/get-invitation/accept-url", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['GET' => 'get_accept_invitation_url']], $single['requirements']);
        $this->map("$single[name]_export", "$single[path]/export", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['GET' => 'export']], $single['requirements']);
        $this->map("$single[name]_activities", "$single[path]/activities", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['GET' => 'activities']], $single['requirements']);
        $this->map("$single[name]_clear_avatar", "$single[path]/clear-avatar", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['DELETE' => 'clear_avatar']], $single['requirements']);
        $this->map("$single[name]_change_role", "$single[path]/change-role", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'change_role']], $single['requirements']);
        $this->map("$single[name]_change_daily_capacity", "$single[path]/change-daily-capacity", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['PUT' => 'change_daily_capacity']], $single['requirements']);
        $this->map("$single[name]_profile_permissions", "$single[path]/profile-permissions", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['GET' => 'profile_permissions']], $single['requirements']);
        $this->map("$single[name]_password_permissions", "$single[path]/password-permissions", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['GET' => 'password_permissions']], $single['requirements']);
        $this->map("$single[name]_job_types", "$single[path]/job-types", ['module' => $single['module'], 'controller' => $single['controller'], 'action' => ['GET' => 'job_types']], $single['requirements']);

        $this->map("$single[name]_sessions", "$single[path]/sessions", ['module' => $single['module'], 'controller' => 'user_sessions', 'action' => ['GET' => 'index', 'DELETE' => 'remove']], $single['requirements']);

        $this->mapResource('api_subscriptions', ['collection_path' => "$single[path]/api-subscriptions", 'collection_requirements' => $single['requirements']]);

        $this->map("$single[name]_workspaces", "$single[path]/workspaces", ['module' => $single['module'], 'controller' => 'user_workspaces', 'action' => ['GET' => 'index']], $single['requirements']);
    }
);

$this->map('user_session', 'user-session', ['controller' => 'user_session', 'action' => ['GET' => 'who_am_i', 'POST' => 'login', 'DELETE' => 'logout']]);
$this->map('authorize_file_access', 'authorize-file-access', ['controller' => 'authorize_file_access', 'action' => ['POST' => 'authorize']]);
$this->map('issue_file_access_token', 'issue-file-access-token', ['controller' => 'user_session', 'action' => ['GET' => 'issue_file_access_token']]);
$this->map('issue_token', 'issue-token', ['controller' => 'user_session', 'action' => ['POST' => 'issue_token']]);
$this->map('issue_token_by_intent', 'issue-token-intent', ['controller' => 'user_session', 'action' => ['POST' => 'issue_token']]); // Alias to /issue-token, these API end-points used to behave differently.
$this->map('accept_invitation', 'accept-invitation', ['controller' => 'user_session', 'action' => ['GET' => 'view_invitation', 'POST' => 'accept_invitation']]);

$this->map('password_recovery_send_code', 'password-recovery/send-code', ['controller' => 'password_recovery', 'action' => ['POST' => 'send_code']]);
$this->map('password_recovery_reset_password', 'password-recovery/reset-password', ['controller' => 'password_recovery', 'action' => ['POST' => 'reset_password']]);

$this->map('socket_auth', 'socket/auth', ['controller' => 'socket_auth', 'action' => ['POST' => 'authenticate']]);

$this->mapResource(
    'companies',
    null,
    function ($collection, $single) {
        $this->map("$collection[name]_all", "$collection[path]/all", ['controller' => $collection['controller'], 'action' => ['GET' => 'all']], $collection['requirements']);
        $this->map("$collection[name]_archive", "$collection[path]/archive", ['controller' => $collection['controller'], 'action' => ['GET' => 'archive']], $collection['requirements']);
        $this->map("$collection[name]_notes", "$collection[path]/notes", ['controller' => $collection['controller'], 'action' => ['GET' => 'notes']], $collection['requirements']);
        $this->map("$single[name]_export", "$single[path]/export", ['controller' => $single['controller'], 'action' => ['GET' => 'export']], $single['requirements']);
        $this->map("$single[name]_projects", "$single[path]/projects", ['controller' => $single['controller'], 'action' => ['GET' => 'projects']], $single['requirements']);
        $this->map("$single[name]_project_names", "$single[path]/project-names", ['controller' => $single['controller'], 'action' => ['GET' => 'project_names']], $single['requirements']);
        $this->map("$single[name]_invoices", "$single[path]/invoices", ['controller' => $single['controller'], 'action' => ['GET' => 'invoices']], $single['requirements']);
    }
);

$this->mapResource(
    'teams',
    null,
    function ($collection, $single) {
        $this->map("$single[name]_members", "$single[path]/members", ['controller' => 'team_members', 'action' => ['GET' => 'index', 'POST' => 'add']]);
        $this->map("$single[name]_member", "$single[path]/members/:user_id", ['controller' => 'team_members', 'action' => ['DELETE' => 'delete']], array_merge($single['requirements'], ['user_id' => UrlMatcherInterface::MATCH_ID]));
    }
);

$this->mapResource('categories');

// Projects
$this->mapResource(
    'projects',
    null,
    function ($collection, $single) {
        $this->map("$collection[name]_filter", "$collection[path]/filter", ['controller' => $collection['controller'], 'action' => ['GET' => 'filter']], $collection['requirements']);
        $this->map("$collection[name]_archive", "$collection[path]/archive", ['controller' => $collection['controller'], 'action' => ['GET' => 'archive']], $collection['requirements']);
        $this->map("$collection[name]_archive_filters", "$collection[path]/archive-filters", ['controller' => $collection['controller'], 'action' => ['GET' => 'filters_for_archived']], $collection['requirements']);
        $this->map("$collection[name]_names", "$collection[path]/names", ['controller' => $collection['controller'], 'action' => ['GET' => 'names']], $collection['requirements']);
        $this->map("$collection[name]_for_screen", "$collection[path]/for-screen", ['controller' => $collection['controller'], 'action' => ['GET' => 'for_screen']], $collection['requirements']);
        $this->map("$collection[name]_with_tracking_enabled", "$collection[path]/with-tracking-enabled", ['controller' => $collection['controller'], 'action' => ['GET' => 'with_tracking_enabled']], $collection['requirements']);
        $this->map("$collection[name]_labels", "$collection[path]/labels", ['controller' => $collection['controller'], 'action' => ['GET' => 'labels']], $collection['requirements']);
        $this->map("$collection[name]_calendar_events", "$collection[path]/calendar-events", ['controller' => $collection['controller'], 'action' => ['GET' => 'calendar_events']], $collection['requirements']);
        $this->map("$collection[name]_categories", "$collection[path]/categories", ['controller' => $collection['controller'], 'action' => ['GET' => 'categories']], $collection['requirements']);
        $this->map("$collection[name]_financial_stats", "$collection[path]/:project_id/financial-stats", ['controller' => $collection['controller'], 'action' => ['GET' => 'financial_stats']], $collection['requirements']);
        $this->map("$collection[name]_budgeting_data", "$collection[path]/budgeting-data", ['controller' => $collection['controller'], 'action' => ['GET' => 'budgeting_data']], $collection['requirements']);
        $this->map("$collection[name]_batch_update_budget_types", "$collection[path]/batch-update-budget-types", ['controller' => $collection['controller'], 'action' => ['PUT' => 'batch_update_budget_types']], $collection['requirements']);

        $this->map("$single[name]_whats_new", "$single[path]/whats-new", ['controller' => $single['controller'], 'action' => ['GET' => 'whats_new']], $single['requirements']);
        $this->map("$single[name]_budget", "$single[path]/budget", ['controller' => $single['controller'], 'action' => ['GET' => 'budget']], $single['requirements']);
        $this->map("$single[name]_export", "$single[path]/export", ['controller' => $single['controller'], 'action' => ['GET' => 'export']], $single['requirements']);
        $this->map("$single[name]_additional_data", "$single[path]/additional-data", ['controller' => $single['controller'], 'action' => ['GET' => 'additional_data']], $single['requirements']);

        // People
        $this->map("$single[name]_members", "$single[path]/members", ['controller' => 'project_members', 'action' => ['GET' => 'index', 'POST' => 'add']]);
        $this->map("$single[name]_member", "$single[path]/members/:user_id", ['controller' => 'project_members', 'action' => ['PUT' => 'replace', 'DELETE' => 'delete']], array_merge($single['requirements'], ['user_id' => UrlMatcherInterface::MATCH_ID]));
        $this->map("$single[name]_revoke_client_access", "$single[path]/revoke-client-access", ['controller' => 'project_members', 'action' => ['PUT' => 'revoke_client_access']]);
        $this->map("$single[name]_responsibilities", "$single[path]/responsibilities", ['controller' => 'project_members', 'action' => ['GET' => 'responsibilities']]);

        $this->map(
            "$single[name]_applied_templates",
            "$single[path]/applied-templates",
            [
                'controller' => 'applied_templates',
                'action' => [
                    'GET' => 'applied_templates',
                    'POST' => 'apply_template',
                ],
            ]
        );

        // ---------------------------------------------------
        //  Task Lists
        // ---------------------------------------------------

        $this->mapResource(
            'task_lists',
            [
                'module' => TasksModule::NAME,
                'collection_path' => "$single[path]/task-lists",
                'collection_requirements' => $collection['requirements'],
            ],
            function ($collection, $single) {
                $this->map(
                    "$collection[name]_all_task_lists",
                    "$collection[path]/all",
                    [
                        'action' => [
                            'GET' => 'all_task_lists',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $collection['requirements']
                );

                $this->map(
                    "$collection[name]_archive",
                    "$collection[path]/archive",
                    [
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $collection['requirements']
                );

                $this->map(
                    "$collection[name]_reorder",
                    "$collection[path]/reorder",
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $collection['requirements']
                );

                $this->map(
                    "$single[name]_move_to_project",
                    "$single[path]/move-to-project",
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_duplicate",
                    "$single[path]/duplicate",
                    [
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_open_tasks",
                    "$single[path]/tasks",
                    [
                        'action' => [
                            'GET' => 'open_tasks',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_completed_tasks",
                    "$single[path]/completed-tasks",
                    [
                        'action' => [
                            'GET' => 'completed_tasks',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$collection[name]_insert",
                    "$collection[path]/insert",
                    [
                        'action' => [
                            'POST' => 'insert',
                        ],
                        'controller' => 'task_lists',
                        'module' => TasksModule::NAME,
                    ],
                    $single['requirements']
                );
            }
        );

        // ---------------------------------------------------
        //  Tasks
        // ---------------------------------------------------

        $this->mapResource(
            'tasks',
            [
                'module' => TasksModule::NAME,
                'collection_path' => "$single[path]/tasks",
                'collection_requirements' => $collection['requirements'],
                'collection_actions' => [
                    'GET' => 'index',
                    'POST' => 'add',
                    'PUT' => 'batch_update',
                ],
            ],
            function ($collection, $single) {
                $this->map("$collection[name]_archive", "$collection[path]/archive", ['action' => ['GET' => 'archive'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $collection['requirements']);
                $this->map("$collection[name]_reorder", "$collection[path]/reorder", ['action' => ['PUT' => 'reorder'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $collection['requirements']);
                $this->map("$collection[name]_for_screen", "$collection[path]/for-screen", ['action' => ['GET' => 'for_screen'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $collection['requirements']);

                $this->map("$single[name]_time_records", "$single[path]/time-records", ['action' => ['GET' => 'time_records'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $single['requirements']);
                $this->map("$single[name]_time_records_info", "$single[path]/time-records/info", ['action' => ['GET' => 'time_records_info'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $single['requirements']);
                $this->map("$single[name]_expenses", "$single[path]/expenses", ['action' => ['GET' => 'expenses'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $single['requirements']);

                $this->mapResource('subtasks', ['module' => TasksModule::NAME, 'collection_path' => "$single[path]/subtasks", 'collection_requirements' => $collection['requirements']], function ($collection, $single) {
                    $this->map("$collection[name]_reorder", "$collection[path]/reorder", ['action' => ['PUT' => 'reorder'], 'controller' => 'subtasks', 'module' => TasksModule::NAME], $collection['requirements']);
                    $this->map("$single[name]_promote_to_task", "$single[path]/promote-to-task", ['action' => ['POST' => 'promote_to_task'], 'controller' => 'subtasks', 'module' => TasksModule::NAME], $single['requirements']);
                });

                $this->map("$single[name]_move_to_project", "$single[path]/move-to-project", ['action' => ['PUT' => 'move_to_project'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $single['requirements']);
                $this->map("$single[name]_duplicate", "$single[path]/duplicate", ['action' => ['POST' => 'duplicate'], 'controller' => 'tasks', 'module' => TasksModule::NAME], $single['requirements']);
            }
        );

        // ---------------------------------------------------
        //  Recurring Tasks
        // ---------------------------------------------------

        $this->mapResource(
            'recurring_tasks',
            [
                'module' => TasksModule::NAME,
                'collection_path' => "$single[path]/recurring-tasks",
                'collection_requirements' => $collection['requirements'],
                'collection_actions' => [
                    'GET' => 'index',
                    'POST' => 'add',
                ],
            ],
            function ($collection, $single) {
                $this->map(
                    "$single[name]_create_task",
                    "$single[path]/create-task",
                    [
                        'action' => [
                            'POST' => 'create_task',
                        ],
                        'controller' => 'recurring_tasks',
                        'module' => TasksModule::NAME,
                    ],
                    $single['requirements']
                );
            }
        );

        // ---------------------------------------------------
        //  Discusssions
        // ---------------------------------------------------

        $this->mapResource(
            'discussions',
            [
                'module' => DiscussionsModule::NAME,
                'collection_path' => "$single[path]/discussions",
                'collection_requirements' => $collection['requirements'],
            ],
            function ($collection, $single) {
                $this->map(
                    "$collection[name]_read_status",
                    "$collection[path]/read-status",
                    [
                        'action' => [
                            'GET' => 'read_status',
                        ],
                        'controller' => 'discussions',
                        'module' => DiscussionsModule::NAME,
                    ],
                    $collection['requirements']
                );

                $this->map(
                    "$single[name]_move_to_project",
                    "$single[path]/move-to-project",
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'discussions',
                        'module' => DiscussionsModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_promote_to_task",
                    "$single[path]/promote-to-task",
                    [
                        'action' => [
                            'POST' => 'promote_to_task',
                        ],
                        'controller' => 'discussions',
                        'module' => DiscussionsModule::NAME,
                    ],
                    $single['requirements']
                );
            }
        );

        // ---------------------------------------------------
        //  Files
        // ---------------------------------------------------

        $this->mapResource(
            'files',
            [
                'module' => FilesModule::NAME,
                'collection_path' => "$single[path]/files",
                'collection_requirements' => $collection['requirements'],
            ],
            function ($collection, $single) {
                $this->map(
                    "$collection[name]_batch",
                    "$collection[path]/batch",
                    [
                        'action' => [
                            'GET' => 'batch_download',
                            'POST' => 'batch_add',
                        ],
                        'controller' => 'files',
                        'module' => FilesModule::NAME,
                    ],
                    $collection['requirements']
                );

                $this->map(
                    "$single[name]_download",
                    "$single[path]/download",
                    [
                        'action' => [
                            'GET' => 'download',
                        ],
                        'controller' => 'files',
                        'module' => FilesModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_move_to_project",
                    "$single[path]/move-to-project",
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'files',
                        'module' => FilesModule::NAME,
                    ],
                    $single['requirements']
                );
            }
        );

        // ---------------------------------------------------
        //  Notes
        // ---------------------------------------------------

        $this->mapResource(
            'notes',
            [
                'module' => NotesModule::NAME,
                'collection_path' => "$single[path]/notes",
                'collection_requirements' => $collection['requirements'],
            ],
            function ($collection, $single) {
                $this->map(
                    "$collection[name]_reorder",
                    "$collection[path]/reorder",
                    [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'notes',
                        'module' => NotesModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_move_to_group",
                    "$single[path]/move-to-group",
                    [
                        'action' => [
                            'PUT' => 'move_to_group',
                        ],
                        'controller' => 'notes',
                        'module' => NotesModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_move_to_project",
                    "$single[path]/move-to-project",
                    [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'notes',
                        'module' => NotesModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_versions",
                    "$single[path]/versions",
                    [
                        'action' => [
                            'GET' => 'versions',
                        ],
                        'controller' => 'notes',
                        'module' => NotesModule::NAME,
                    ],
                    $single['requirements']
                );
            }
        );

        $this->mapResource(
            'note_groups',
            [
                'module' => NotesModule::NAME,
                'collection_path' => "$single[path]/note-groups",
                'collection_requirements' => $collection['requirements'],
            ],
            function ($collection, $single) {
                $this->map(
                    "$single[name]_notes",
                    "$single[path]/notes",
                    [
                        'controller' => 'note_groups',
                        'action' => [
                            'GET' => 'notes',
                        ],
                        'module' => NotesModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_reorder_notes",
                    "$single[path]/reorder-notes",
                    [
                        'controller' => 'note_groups',
                        'action' => [
                            'PUT' => 'reorder_notes',
                        ],
                        'module' => NotesModule::NAME,
                    ],
                    $single['requirements']
                );

                $this->map(
                    "$single[name]_move_to_group",
                    "$single[path]/move-to-group",
                    [
                        'controller' => 'note_groups',
                        'action' => [
                            'PUT' => 'move_to_group',
                        ],
                        'module' => NotesModule::NAME,
                    ],
                    $single['requirements']
                );
            }
        );

        // ---------------------------------------------------
        //  Time Records
        // ---------------------------------------------------

        $this->mapResource(
            'time_records',
            [
                'module' => TrackingModule::NAME,
                'collection_path' => "$single[path]/time-records",
                'collection_requirements' => $collection['requirements'],
            ],
            function ($collection, $single) {
                $this->map(
                    "$collection[name]_filtered_by_date",
                    "$collection[path]/filtered-by-date",
                    [
                        'module' => $collection['module'],
                        'controller' => $collection['controller'],
                        'action' => [
                            'GET' => 'filtered_by_date',
                        ],
                    ],
                    $collection['requirements']
                );

                $this->map(
                    "$single[name]_move",
                    "$single[path]/move",
                    [
                        'module' => $single['module'],
                        'controller' => $single['controller'],
                        'action' => [
                            'PUT' => 'move',
                        ],
                    ],
                    $single['requirements']
                );
            }
        );

        // ---------------------------------------------------
        //  Expenses
        // ---------------------------------------------------

        $this->mapResource(
            'expenses',
            [
                'module' => TrackingModule::NAME,
                'collection_path' => "$single[path]/expenses",
                'collection_requirements' => $collection['requirements'],
            ],
            function ($collection, $single) {
                $this->map(
                    "$single[name]_move",
                    "$single[path]/move",
                    [
                        'module' => $single['module'],
                        'controller' => $single['controller'],
                        'action' => [
                            'PUT' => 'move',
                        ],
                    ],
                    $single['requirements']
                );
            }
        );
    }
);

$this->map('project_labels', 'labels/project-labels', ['controller' => 'labels', 'action' => ['GET' => 'project_labels']]);
$this->map('task_labels', 'labels/task-labels', ['controller' => 'labels', 'action' => ['GET' => 'task_labels']]);

$this->mapResource('project_templates', [], function ($collection, $single) {
    $this->map("$single[name]_duplicate", "$single[path]/duplicate", ['controller' => 'project_templates', 'action' => ['POST' => 'duplicate']]);
    $this->map("$single[name]_reorder", "$single[path]/reorder", ['controller' => 'project_templates', 'action' => ['PUT' => 'reorder']]);

    $this->mapResource('project_template_elements', ['collection_path' => "$single[path]/elements", 'collection_requirements' => $collection['requirements']], function ($collection, $single) {
        $this->map("$collection[name]_batch", "$collection[path]/batch", ['action' => ['POST' => 'batch_add'], 'controller' => 'project_template_elements'], $collection['requirements']);
        $this->map("$single[name]_download", "$collection[path]/download", ['action' => ['GET' => 'download'], 'controller' => 'project_template_elements'], $collection['requirements']);
        $this->map("$collection[name]_reorder", "$collection[path]/reorder", ['action' => ['PUT' => 'reorder'], 'controller' => 'project_template_elements'], $collection['requirements']);
    });
});

$this->map(
    'project_template_task_dependencies',
    'project-templates/dependencies/tasks/:task_id',
    [
        'controller' => 'project_template_task_dependencies',
        'action' => [
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'delete',
        ],
    ]
);

$this->map(
    'project_template_task_dependencies_suggestions',
    'project-templates/dependencies/tasks/:task_id/suggestions',
    [
        'controller' => 'project_template_task_dependencies',
        'action' => [
            'GET' => 'dependency_suggestions',
        ],
    ]
);

$this->map('projects_with_people_permissions', '/projects/with-people-permissions', ['controller' => 'projects', 'action' => ['GET' => 'with_people_permissions']]);

$this->map('user_projects', 'users/:user_id/projects', ['controller' => 'users', 'action' => ['GET' => 'projects', 'POST' => 'add_to_projects']], ['user_id' => UrlMatcherInterface::MATCH_ID]);
$this->map('user_project_ids', 'users/:user_id/projects/ids', ['controller' => 'users', 'action' => ['GET' => 'project_ids']], ['user_id' => UrlMatcherInterface::MATCH_ID]);
$this->map('feedback', 'feedback', ['controller' => 'feedback', 'action' => ['POST' => 'send']]);
$this->map('feedback_check', 'feedback/check', ['controller' => 'feedback', 'action' => ['GET' => 'check']]);
$this->map('new_features', 'new-features', ['controller' => 'new_features', 'action' => ['GET' => 'list_new_features']]);
$this->map('maintenance_mode', 'maintenance-mode', ['controller' => 'maintenance_mode', 'action' => ['GET' => 'show_settings', 'PUT' => 'save_settings']]);
$this->map('security', 'security', ['controller' => 'security', 'action' => ['GET' => 'show_settings', 'PUT' => 'save_settings']]);
$this->map('versions', 'system/versions/old-versions', ['controller' => 'versions', 'action' => ['GET' => 'check_old_versions', 'DELETE' => 'delete_old_versions']]);

// Basecamp integration controller
$this->map('basecamp_check_credentials', 'integrations/basecamp-importer/check-credentials', ['controller' => 'basecamp_importer_integration', 'action' => ['POST' => 'check_credentials'], 'integration_type' => 'basecamp-importer']);
$this->map('basecamp_schedule_import', 'integrations/basecamp-importer/schedule-import', ['controller' => 'basecamp_importer_integration', 'action' => ['POST' => 'schedule_import'], 'integration_type' => 'basecamp-importer']);
$this->map('basecamp_start_over', 'integrations/basecamp-importer/start-over', ['controller' => 'basecamp_importer_integration', 'action' => ['POST' => 'start_over'], 'integration_type' => 'basecamp-importer']);
$this->map('basecamp_check_status', 'integrations/basecamp-importer/check-status', ['controller' => 'basecamp_importer_integration', 'action' => ['GET' => 'check_status'], 'integration_type' => 'basecamp-importer']);
$this->map('basecamp_invite_users', 'integrations/basecamp-importer/invite-users', ['controller' => 'basecamp_importer_integration', 'action' => ['POST' => 'invite_users'], 'integration_type' => 'basecamp-importer']);

// Client+
$this->map('client_plus', 'integrations/client-plus', ['controller' => 'client_plus_integration', 'action' => ['POST' => 'activate', 'DELETE' => 'deactivate'], 'integration_type' => 'client_plus']);

// Trello integration controller
$this->map('trello_request_url', 'integrations/trello-importer/request-url', ['controller' => 'trello_importer_integration', 'action' => ['GET' => 'get_request_url'], 'integration_type' => 'trello-importer']);
$this->map('trello_authorize', 'integrations/trello-importer/authorize', ['controller' => 'trello_importer_integration', 'action' => ['PUT' => 'authorize'], 'integration_type' => 'trello-importer']);
$this->map('trello_schedule_import', 'integrations/trello-importer/schedule-import', ['controller' => 'trello_importer_integration', 'action' => ['POST' => 'schedule_import'], 'integration_type' => 'trello-importer']);
$this->map('trello_start_over', 'integrations/trello-importer/start-over', ['controller' => 'trello_importer_integration', 'action' => ['POST' => 'start_over'], 'integration_type' => 'trello-importer']);
$this->map('trello_check_status', 'integrations/trello-importer/check-status', ['controller' => 'trello_importer_integration', 'action' => ['GET' => 'check_status'], 'integration_type' => 'trello-importer']);
$this->map('trello_invite_users', 'integrations/trello-importer/invite-users', ['controller' => 'trello_importer_integration', 'action' => ['GET' => 'invite_users'], 'integration_type' => 'trello-importer']);

// Asana integration controller
$this->map('asana_request_url', 'integrations/asana-importer/request-url', ['controller' => 'asana_importer_integration', 'action' => ['GET' => 'get_request_url'], 'integration_type' => 'asana-importer']);
$this->map('asana_authorize', 'integrations/asana-importer/authorize', ['controller' => 'asana_importer_integration', 'action' => ['PUT' => 'authorize'], 'integration_type' => 'asana-importer']);
$this->map('asana_schedule_import', 'integrations/asana-importer/schedule-import', ['controller' => 'asana_importer_integration', 'action' => ['POST' => 'schedule_import'], 'integration_type' => 'asana-importer']);
$this->map('asana_start_over', 'integrations/asana-importer/start-over', ['controller' => 'asana_importer_integration', 'action' => ['POST' => 'start_over'], 'integration_type' => 'asana-importer']);
$this->map('asana_check_status', 'integrations/asana-importer/check-status', ['controller' => 'asana_importer_integration', 'action' => ['GET' => 'check_status'], 'integration_type' => 'asana-importer']);
$this->map('asana_invite_users', 'integrations/asana-importer/invite-users', ['controller' => 'asana_importer_integration', 'action' => ['GET' => 'invite_users'], 'integration_type' => 'asana-importer']);

// Sample projects controller
$this->map('sample_projects', 'integrations/sample-projects', ['controller' => 'sample_projects_integration', 'action' => ['GET' => 'index', 'POST' => 'import'], 'integration_type' => 'sample-projects']);

$this->map('slack_connect', 'integrations/slack/connect', ['controller' => 'slack_integration', 'action' => ['PUT' => 'connect'], 'integration_type' => 'slack']);
$this->map('slack_notification_channel', 'integrations/slack/notification-channels/:notification_channel_id', ['controller' => 'slack_integration', 'action' => ['PUT' => 'edit', 'DELETE' => 'delete'], 'integration_type' => 'slack', 'notification_channel_id' => UrlMatcherInterface::MATCH_ID]);

// Webhooks integration controller
$this->map('webhooks_integration_ids', 'integrations/webhooks/:webhook_id', ['controller' => 'webhooks_integration', 'action' => ['PUT' => 'edit', 'DELETE' => 'delete'], 'integration_type' => 'webhooks', 'webhook_id' => UrlMatcherInterface::MATCH_ID]);
$this->map('webhooks_integration', 'integrations/webhooks', ['controller' => 'webhooks_integration', 'action' => ['POST' => 'add'], 'integration_type' => 'webhooks']);

// Calendar feeds
$this->map('calendar_feeds', 'calendar-feeds', ['controller' => 'calendar_feeds', 'action' => ['GET' => 'index']]);
$this->map('calendar_feeds_project', 'calendar-feeds/projects/:project_id', ['controller' => 'calendar_feeds', 'action' => ['GET' => 'project']], ['project_id' => UrlMatcherInterface::MATCH_ID]);
$this->map('calendar_feeds_calendar', 'calendar-feeds/calendars/:calendar_id', ['controller' => 'calendar_feeds', 'action' => ['GET' => 'calendar']], ['calendar_id' => UrlMatcherInterface::MATCH_ID]);

// Zapier integration controller
$this->map('zapier_integration', 'integrations/zapier', ['controller' => 'zapier_integration', 'action' => ['GET' => 'get_data', 'POST' => 'enable', 'DELETE' => 'disable'], 'integration_type' => 'zapier']);
$this->map('zapier_integration_payload_example', 'integrations/zapier/payload-examples/:event_type', ['controller' => 'zapier_integration', 'action' => ['GET' => 'payload_example'], 'integration_type' => 'zapier'], ['event_type' => UrlMatcherInterface::MATCH_SLUG]);

// Zapier Webhooks REST Endpoint
$this->mapResource('zapier_webhooks', ['collection_path' => '/integrations/zapier/webhooks']);

// OneLogin integration
$this->map('one_login_credentials', 'integrations/one-login/credentials', ['controller' => 'one_login_integration', 'action' => ['POST' => 'credentials'], 'integration_type' => 'one-login']);
$this->map('one_login_enable', 'integrations/one-login/enable', ['controller' => 'one_login_integration', 'action' => ['GET' => 'enable'], 'integration_type' => 'one-login']);
$this->map('one_login_disable', 'integrations/one-login/disable', ['controller' => 'one_login_integration', 'action' => ['GET' => 'disable'], 'integration_type' => 'one-login']);

// Wrike integration controller
$this->map('wrike_authorize', 'integrations/wrike-importer/authorize', ['controller' => 'wrike_importer_integration', 'action' => ['PUT' => 'authorize'], 'integration_type' => 'wrike-importer']);
$this->map('wrike_schedule_import', 'integrations/wrike-importer/schedule-import', ['controller' => 'wrike_importer_integration', 'action' => ['POST' => 'schedule_import'], 'integration_type' => 'wrike-importer']);
$this->map('wrike_start_over', 'integrations/wrike-importer/start-over', ['controller' => 'wrike_importer_integration', 'action' => ['POST' => 'start_over'], 'integration_type' => 'wrike-importer']);
$this->map('wrike_check_status', 'integrations/wrike-importer/check-status', ['controller' => 'wrike_importer_integration', 'action' => ['GET' => 'check_status'], 'integration_type' => 'wrike-importer']);
$this->map('wrike_invite_users', 'integrations/wrike-importer/invite-users', ['controller' => 'wrike_importer_integration', 'action' => ['GET' => 'invite_users'], 'integration_type' => 'wrike-importer']);

$this->map('cta_notifications', 'cta-notifications/:notification_type', ['controller' => 'cta_notifications', 'action' => ['GET' => 'show']]);
$this->map('cta_notifications_dismiss', 'cta-notifications/:notification_type/dismiss', ['controller' => 'cta_notifications', 'action' => ['POST' => 'dismiss']]);

$this->map(
    'reactions',
    'reactions/:parent_type/:parent_id',
    [
        'action' => [
            'POST' => 'add_reaction',
            'DELETE' => 'remove_reaction',
        ],
        'controller' => 'reactions',
    ],
    [
        'parent_type' => UrlMatcherInterface::MATCH_SLUG,
        'parent_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'reaction',
    'reactions/:reaction_id',
    [
        'controller' => 'reactions',
        'action' => [],
    ],
    [
        'reaction_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'logger',
    'logger/:log_level',
    [
        'controller' => 'logger',
        'action' => [
            'POST' => 'add',
        ],
    ],
    [
        'log_level' => UrlMatcherInterface::MATCH_WORD,
    ]
);

$this->map(
    'comments',
    'comments/:parent_type/:parent_id',
    [
        'controller' => 'comments',
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
    'comment',
    'comments/:comment_id',
    [
        'controller' => 'comments',
        'action' => [
            'GET' => 'view',
            'PUT' => 'edit',
            'DELETE' => 'delete',
        ],
    ],
    [
        'comment_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'find-conversation',
    'conversations/:type/:id',
    [
        'controller' => 'conversations',
        'action' => [
            'GET' => 'find',
        ],
    ],
    [
        'type' => UrlMatcherInterface::MATCH_SLUG,
        'id' => UrlMatcherInterface::MATCH_ID,
    ]
);
$this->map(
    'conversations',
    'conversations',
    [
        'controller' => 'conversations',
        'action' => [
            'POST' => 'create',
            'GET' => 'index',
        ],
    ]
);

$this->map(
    'conversation',
    'conversations/:conversation_id',
    [
        'controller' => 'conversations',
        'action' => [
            'GET' => 'view',
            'DELETE' => 'delete',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'group_conversation_leave',
    'conversations/:conversation_id/leave',
    [
        'controller' => 'group_conversations',
        'action' => [
            'PUT' => 'leave',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
$this->map(
    'group_conversation_remove',
    'conversations/:conversation_id/remove/:user_id',
    [
        'controller' => 'group_conversations',
        'action' => [
            'DELETE' => 'remove',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'group_conversation_invite',
    'conversations/:conversation_id/invite',
    [
        'controller' => 'group_conversations',
        'action' => [
            'POST' => 'invite',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'group_conversation_rename',
    'conversations/:conversation_id/rename',
    [
        'controller' => 'group_conversations',
        'action' => [
            'PUT' => 'rename',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
$this->map(
    'group_conversation_admins',
    'conversations/:conversation_id/admins/:user_id',
    [
        'controller' => 'group_conversations',
        'action' => [
            'POST' => 'add_admin',
            'DELETE' => 'remove_admin',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'conversation_messages',
    'conversations/:conversation_id/messages',
    [
        'controller' => 'conversation_messages',
        'action' => [
            'GET' => 'index',
            'POST' => 'create',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'conversation_messages_mark_as_unread',
    'conversations/:conversation_id/messages/:message_id/mark-as-unread',
    [
        'controller' => 'conversation_messages',
        'action' => [
            'PUT' => 'mark_as_unread',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
        'message_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'conversation_messages_create_task',
    'conversations/:conversation_id/messages/create-task',
    [
        'controller' => 'conversation_messages',
        'action' => [
            'POST' => 'create_task',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'conversation_message',
    'conversations/:conversation_id/messages/:message_id',
    [
        'controller' => 'conversation_messages',
        'action' => [
            'GET' => 'view',
            'PUT' => 'edit',
            'DELETE' => 'delete',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
        'message_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'conversation_attachments',
    'conversations/:conversation_id/attachments',
    [
        'controller' => 'conversations',
        'action' => [
            'GET' => 'attachments',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'user_conversations',
    'user-conversations',
    [
        'controller' => 'conversations',
        'action' => [
            'GET' => 'user_conversations',
        ],
    ]
);

$this->map(
    'user_conversation',
    'user-conversations/:conversation_id',
    [
        'controller' => 'user_conversations',
        'action' => [
            'PUT' => 'edit',
        ],
    ],
    [
        'conversation_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'user_shortcuts',
    'shortcuts/:shortcut_id',
    [
        'controller' => 'shortcuts',
        'action' => [
            'GET' => 'view',
            'PUT' => 'edit',
            'DELETE' => 'delete',
        ],
    ],
    [
        'shortcut_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'user_shortcuts_collection',
    'shortcuts',
    [
        'controller' => 'shortcuts',
        'action' => [
            'GET' => 'index',
            'POST' => 'create',
            'DELETE' => 'batch_delete',
        ],
    ]
);

$this->map(
    'reorder_shortcuts',
    'shortcuts/reorder',
    [
        'controller' => 'shortcuts',
        'action' => [
            'PUT' => 'reorder',
        ],
    ],
);

$this->mapResource('activity_logs'); // Needed so ActivityLog can be described :(

$this->map(
    'global_activities',
    'activities',
    [
        'controller' => 'activities',
        'action' => ['GET' => 'index'],
    ]
);
$this->map(
    'activities_project',
    'activities/projects/:project_id',
    [
        'controller' => 'activities',
        'action' => ['GET' => 'project'],
    ],
    [
        'project_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
$this->map(
    'activities_user',
    'activities/users/:user_id',
    [
        'controller' => 'activities',
        'action' => ['GET' => 'user'],
    ],
    [
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'whats_new',
    'whats-new',
    [
        'controller' => 'whats_new',
        'action' => ['GET' => 'index'],
    ]
);

$this->map(
    'whats_new_daily',
    'whats-new/daily/:day',
    [
        'controller' => 'whats_new',
        'action' => ['GET' => 'daily'],
    ],
    [
        'day' => UrlMatcherInterface::MATCH_DATE,
    ]
);

$this->map(
    'workload_tasks',
    'workload/tasks',
    [
        'controller' => 'workload',
        'action' => [
            'GET' => 'workload_tasks',
        ],
    ]
);

$this->map(
    'workload_projects',
    'workload/projects',
    [
        'controller' => 'workload',
        'action' => [
            'GET' => 'workload_projects',
        ],
    ]
);

$this->map(
    'availability_types',
    'availability-types',
    [
        'controller' => 'availability_types',
        'action' => [
            'GET' => 'index',
            'POST' => 'add',
        ],
    ]
);

$this->map(
    'availability_type',
    'availability-types/:availability_type_id',
    [
        'controller' => 'availability_types',
        'action' => [
            'GET' => 'view',
            'PUT' => 'edit',
            'DELETE' => 'delete',
        ],
    ],
    [
        'availability_type_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'availability_records',
    'availability-records/users/:user_id',
    [
        'controller' => 'availability_records',
        'action' => [
            'GET' => 'index',
            'POST' => 'add',
        ],
    ],
    [
        'user_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'availability_record',
    'availability-records/:availability_record_id',
    [
        'controller' => 'availability_records',
        'action' => [
            'DELETE' => 'delete',
        ],
    ],
    [
        'availability_record_id' => UrlMatcherInterface::MATCH_ID,
    ]
);

$this->map(
    'all_availability_records',
    'availability-records/all',
    [
        'controller' => 'availability_records',
        'action' => [
            'GET' => 'all',
        ],
    ]
);

$this->map(
    'feature_pointers',
    'feature-pointers',
    [
        'controller' => 'feature_pointers',
        'action' => [
            'GET' => 'index',
        ],
    ]
);

$this->map(
    'feature_pointer',
    'feature-pointers/:feature_pointer_id',
    [
        'controller' => 'feature_pointers',
        'action' => [
            'PUT' => 'dismiss',
        ],
    ],
    [
        'feature_pointer_id' => UrlMatcherInterface::MATCH_ID,
    ]
);
