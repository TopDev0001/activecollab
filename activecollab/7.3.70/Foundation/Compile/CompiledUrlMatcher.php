<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Compile;

use ActiveCollab\Foundation\Urls\Router\UrlMatcher\CompiledUrlMatcher as BaseCompiledUrlMatcher;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedCollection;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedEntity;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRoute;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedRouteInterface;

class CompiledUrlMatcher extends BaseCompiledUrlMatcher
{
    protected function matchRouteFrom(string $path, string $query_string): ?MatchedRouteInterface
    {
        $matches = null;

        if (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/budget-thresholds$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'budget_thresholds',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'budget_thresholds_index', 
                $url_params
            );
        } elseif (preg_match('/^users\\/internal-rates\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'id',
                    ],
                [
                        'controller' => 'internal_rate',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'delete_internal_rate', 
                $url_params
            );
        } elseif (preg_match('/^users\\/internal-rates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'internal_rate',
                        'action' => [
                            'GET' => 'all',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'internal_rates_all', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/internal-rates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'internal_rate',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'internal_rates', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/internal-rate$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'internal_rate',
                        'action' => [
                            'GET' => 'view',
                            'POST' => 'add',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'internal_rate', 
                $url_params
            );
        } elseif (preg_match('/^stopwatches\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'id',
                    ],
                [
                        'controller' => 'stopwatch',
                        'action' => [
                            'DELETE' => 'delete',
                            'PUT' => 'edit',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'stopwatches_delete', 
                $url_params
            );
        } elseif (preg_match('/^stopwatches\\/(\\d+)\\/resume$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'id',
                    ],
                [
                        'controller' => 'stopwatch',
                        'action' => [
                            'PUT' => 'resume',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'stopwatches_resume', 
                $url_params
            );
        } elseif (preg_match('/^stopwatches\\/(\\d+)\\/pause$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'id',
                    ],
                [
                        'controller' => 'stopwatch',
                        'action' => [
                            'PUT' => 'pause',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'stopwatches_pause', 
                $url_params
            );
        } elseif (preg_match('/^stopwatches\\/offset$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'stopwatch',
                        'action' => [
                            'POST' => 'offset',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'stopwatches_offset', 
                $url_params
            );
        } elseif (preg_match('/^stopwatches$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'stopwatch',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'start',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'stopwatches_index', 
                $url_params
            );
        } elseif (preg_match('/^expense-categories\\/edit-batch$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'expense_categories',
                        'action' => [
                            'PUT' => 'batch_edit',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'expense_categories_batch_edit', 
                $url_params
            );
        } elseif (preg_match('/^expense-categories\\/default$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'expense_categories',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'expense_categories_default', 
                $url_params
            );
        } elseif (preg_match('/^expense-categories\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'expense_category_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'expense_categories',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'expense_category', 
                $url_params,
                'ExpenseCategory',
                $url_params['expense_category_id'] ?? 0
            );
        } elseif (preg_match('/^expense-categories$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'tracking',
                        'controller' => 'expense_categories',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'expense_categories', 
                $url_params,
                'ExpenseCategories'
            );
        } elseif (preg_match('/^job-types\\/edit-batch$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'job_types',
                        'action' => [
                            'PUT' => 'batch_edit',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'job_types_batch_edit', 
                $url_params
            );
        } elseif (preg_match('/^job-types\\/default$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'job_types',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'job_types_default', 
                $url_params
            );
        } elseif (preg_match('/^job-types\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'job_type_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'job_types',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'job_type', 
                $url_params,
                'JobType',
                $url_params['job_type_id'] ?? 0
            );
        } elseif (preg_match('/^job-types$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'tracking',
                        'controller' => 'job_types',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'job_types', 
                $url_params,
                'JobTypes'
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/time-records\\/filtered-by-date$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'user_time_records',
                        'action' => [
                            'GET' => 'filtered_by_date',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_time_records_filtered_by_date', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/time-records$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'user_time_records',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_time_records', 
                $url_params
            );
        } elseif (preg_match('/^expenses\\/([a-z0-9\\-\\._]+)\\/(\\d+)\\/info$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'controller' => 'expenses_by_parent',
                        'action' => [
                            'GET' => 'info',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'expenses_by_parent_info', 
                $url_params
            );
        } elseif (preg_match('/^time-records\\/([a-z0-9\\-\\._]+)\\/(\\d+)\\/info$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'controller' => 'time_records_by_parent',
                        'action' => [
                            'GET' => 'info',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'time_records_by_parent_info', 
                $url_params
            );
        } elseif (preg_match('/^time-records\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'controller' => 'time_records_by_parent',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'time_records_by_parent', 
                $url_params
            );
        } elseif (preg_match('/^time-records$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'timesheet_report',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tracking',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'time_records_report', 
                $url_params
            );
        } elseif (preg_match('/^tasks\\/(\\d+)\\/reschedule$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'task_id',
                    ],
                [
                        'controller' => 'task_reschedule',
                        'action' => [
                            'GET' => 'reschedule_simulation',
                            'POST' => 'make_reschedule',
                        ],
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_reschedule', 
                $url_params
            );
        } elseif (preg_match('/^dependencies\\/tasks\\/(\\d+)\\/suggestions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'task_id',
                    ],
                [
                        'controller' => 'task_dependencies',
                        'action' => [
                            'GET' => 'dependency_suggestions',
                        ],
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_dependency_suggestions', 
                $url_params
            );
        } elseif (preg_match('/^dependencies\\/project\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'project_dependencies',
                        'action' => [
                            'GET' => 'view',
                        ],
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_task_dependencies', 
                $url_params
            );
        } elseif (preg_match('/^dependencies\\/tasks\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'task_id',
                    ],
                [
                        'controller' => 'task_dependencies',
                        'action' => [
                            'GET' => 'view',
                            'POST' => 'create',
                            'PUT' => 'delete',
                        ],
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_dependencies', 
                $url_params
            );
        } elseif (preg_match('/^reports\\/unscheduled-tasks\\/count-by-project$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'unscheduled_tasks',
                        'action' => [
                            'GET' => 'count_by_project',
                        ],
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'unscheduled_task_counts', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/tasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'user_tasks',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_tasks', 
                $url_params
            );
        } elseif (preg_match('/^teams\\/(\\d+)\\/tasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'team_id',
                    ],
                [
                        'controller' => 'team_tasks',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'team_tasks', 
                $url_params
            );
        } elseif (preg_match('/^xero\\/invoices\\/sync$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'xero_invoices',
                        'action' => [
                            'PUT' => 'sync',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'xero_invoices_sync', 
                $url_params
            );
        } elseif (preg_match('/^xero\\/invoices\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'xero_invoice_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'xero_invoices',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'xero_invoice', 
                $url_params,
                'XeroInvoice',
                $url_params['xero_invoice_id'] ?? 0
            );
        } elseif (preg_match('/^xero\\/invoices$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'xero_invoices',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'xero_invoices', 
                $url_params,
                'XeroInvoices'
            );
        } elseif (preg_match('/^integrations\\/xero\\/data$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'xero_integration',
                        'action' => [
                            'GET' => 'get_data',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'xero_integration', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/xero\\/authorize$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'xero_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'xero_authorize', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/xero\\/authorization-url$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'xero_integration',
                        'action' => [
                            'GET' => 'get_authorization_url',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'xero_request_url', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/xero\\/payments$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'xero_integration',
                        'action' => [
                            'GET' => 'sync_payments',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'xero_payments', 
                $url_params
            );
        } elseif (preg_match('/^quickbooks\\/invoices\\/sync$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'quickbooks_invoices',
                        'action' => [
                            'PUT' => 'sync',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'quickbooks_invoices_sync', 
                $url_params
            );
        } elseif (preg_match('/^quickbooks\\/invoices\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'quickbooks_invoice_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'quickbooks_invoices',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'quickbooks_invoice', 
                $url_params,
                'QuickbooksInvoice',
                $url_params['quickbooks_invoice_id'] ?? 0
            );
        } elseif (preg_match('/^quickbooks\\/invoices$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'quickbooks_invoices',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'quickbooks_invoices', 
                $url_params,
                'QuickbooksInvoices'
            );
        } elseif (preg_match('/^integrations\\/quickbooks\\/suggestion$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'get_suggested_transaction_number',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'quickbooks_integration_suggestion', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/quickbooks\\/data$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'get_data',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'quickbooks_integration', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/quickbooks\\/authorize$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'quickbooks_authorize', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/quickbooks\\/request-url$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'get_request_url',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'quickbooks_request_url', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/quickbooks\\/payments$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'quickbooks_integration',
                        'action' => [
                            'GET' => 'sync_payments',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'quickbooks_payments', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/addresses-for-invoicing$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'company_addresses',
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'company_addresses_for_invoicing', 
                $url_params
            );
        } elseif (preg_match('/^tax-rates\\/default$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'tax_rates',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                            'DELETE' => 'unset_default',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'tax_rates_default', 
                $url_params
            );
        } elseif (preg_match('/^tax-rates\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'tax_rate_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'tax_rates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'tax_rate', 
                $url_params,
                'TaxRate',
                $url_params['tax_rate_id'] ?? 0
            );
        } elseif (preg_match('/^tax-rates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'tax_rates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'tax_rates', 
                $url_params,
                'TaxRates'
            );
        } elseif (preg_match('/^invoice-item-templates\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_item_template_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoice_item_templates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'invoice_item_template', 
                $url_params,
                'InvoiceItemTemplate',
                $url_params['invoice_item_template_id'] ?? 0
            );
        } elseif (preg_match('/^invoice-item-templates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoice_item_templates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'invoice_item_templates', 
                $url_params,
                'InvoiceItemTemplates'
            );
        } elseif (preg_match('/^invoice-note-templates\\/default$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'invoice_note_templates',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                            'DELETE' => 'unset_default',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_note_templates_default', 
                $url_params
            );
        } elseif (preg_match('/^invoice-note-templates\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_note_template_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoice_note_templates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'invoice_note_template', 
                $url_params,
                'InvoiceNoteTemplate',
                $url_params['invoice_note_template_id'] ?? 0
            );
        } elseif (preg_match('/^invoice-note-templates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoice_note_templates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'invoice_note_templates', 
                $url_params,
                'InvoiceNoteTemplates'
            );
        } elseif (preg_match('/^invoices\\/suggest-number$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'suggest_number',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoices_suggest_number', 
                $url_params
            );
        } elseif (preg_match('/^invoice-template$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'invoice_template',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_template', 
                $url_params
            );
        } elseif (preg_match('/^recurring-profiles\\/(\\d+)\\/next-trigger-on$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'recurring_profile_id',
                    ],
                [
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'next_trigger_on',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'recurring_profile_next_trigger_on', 
                $url_params
            );
        } elseif (preg_match('/^recurring-profiles\\/trigger$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'POST' => 'trigger',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'recurring_profiles_trigger', 
                $url_params
            );
        } elseif (preg_match('/^recurring-profiles\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'recurring_profiles_archive', 
                $url_params
            );
        } elseif (preg_match('/^recurring-profiles\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'recurring_profile_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'recurring_profile', 
                $url_params,
                'RecurringProfile',
                $url_params['recurring_profile_id'] ?? 0
            );
        } elseif (preg_match('/^recurring-profiles$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'recurring_profiles',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'recurring_profiles', 
                $url_params,
                'RecurringProfiles'
            );
        } elseif (preg_match('/^estimates\\/(\\d+)\\/duplicate$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'estimate_id',
                    ],
                [
                        'controller' => 'estimates',
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'estimate_duplicate', 
                $url_params
            );
        } elseif (preg_match('/^estimates\\/(\\d+)\\/export$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'estimate_id',
                    ],
                [
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'estimate_export', 
                $url_params
            );
        } elseif (preg_match('/^estimates\\/(\\d+)\\/send$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'estimate_id',
                    ],
                [
                        'controller' => 'estimates',
                        'action' => [
                            'PUT' => 'send',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'estimate_send', 
                $url_params
            );
        } elseif (preg_match('/^estimates\\/private-notes$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'private_notes',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'estimates_private_notes', 
                $url_params
            );
        } elseif (preg_match('/^estimates\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'estimates_archive', 
                $url_params
            );
        } elseif (preg_match('/^estimates\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'estimate_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'estimate', 
                $url_params,
                'Estimate',
                $url_params['estimate_id'] ?? 0
            );
        } elseif (preg_match('/^estimates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'estimates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'estimates', 
                $url_params,
                'Estimates'
            );
        } elseif (preg_match('/^s\\/invoice$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'public_invoice',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'make_payment',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_public', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)\\/mark-zero-invoice-as-paid$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'POST' => 'mark_zero_invoice_as_paid',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_mark_zero_invoice_as_paid', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)\\/mark-as-sent$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'POST' => 'mark_as_sent',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_mark_as_sent', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)\\/related-records$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'DELETE' => 'release_related_records',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_related_records', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)\\/cancel$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'PUT' => 'cancel',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_cancel', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)\\/duplicate$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_duplicate', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)\\/export$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_export', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)\\/send$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'PUT' => 'send',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoice_send', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/projects$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'projects_invoicing_data',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoices_projects', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/preview-items$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'preview_items',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoices_preview_items', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/private-notes$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'private_notes',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoices_private_notes', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'invoicing',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'invoices_archive', 
                $url_params
            );
        } elseif (preg_match('/^invoices\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'invoice', 
                $url_params,
                'Invoice',
                $url_params['invoice_id'] ?? 0
            );
        } elseif (preg_match('/^invoices$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoices',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'invoices', 
                $url_params,
                'Invoices'
            );
        } elseif (preg_match('/^invoice-items\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'invoice_item_id',
                    ],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoice_items',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'invoice_item', 
                $url_params,
                'InvoiceItem',
                $url_params['invoice_item_id'] ?? 0
            );
        } elseif (preg_match('/^invoice-items$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'invoicing',
                        'controller' => 'invoice_items',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'invoice_items', 
                $url_params,
                'InvoiceItems'
            );
        } elseif (preg_match('/^feature-pointers\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'feature_pointer_id',
                    ],
                [
                        'controller' => 'feature_pointers',
                        'action' => [
                            'PUT' => 'dismiss',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'feature_pointer', 
                $url_params
            );
        } elseif (preg_match('/^feature-pointers$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'feature_pointers',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'feature_pointers', 
                $url_params
            );
        } elseif (preg_match('/^availability-records\\/all$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'availability_records',
                        'action' => [
                            'GET' => 'all',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'all_availability_records', 
                $url_params
            );
        } elseif (preg_match('/^availability-records\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'availability_record_id',
                    ],
                [
                        'controller' => 'availability_records',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'availability_record', 
                $url_params
            );
        } elseif (preg_match('/^availability-records\\/users\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'availability_records',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'availability_records', 
                $url_params
            );
        } elseif (preg_match('/^availability-types\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'availability_type_id',
                    ],
                [
                        'controller' => 'availability_types',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'availability_type', 
                $url_params
            );
        } elseif (preg_match('/^availability-types$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'availability_types',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'availability_types', 
                $url_params
            );
        } elseif (preg_match('/^workload\\/projects$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'workload',
                        'action' => [
                            'GET' => 'workload_projects',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'workload_projects', 
                $url_params
            );
        } elseif (preg_match('/^workload\\/tasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'workload',
                        'action' => [
                            'GET' => 'workload_tasks',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'workload_tasks', 
                $url_params
            );
        } elseif (preg_match('/^whats-new\\/daily\\/(([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]))$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'day',
                    ],
                [
                        'controller' => 'whats_new',
                        'action' => [
                            'GET' => 'daily',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'whats_new_daily', 
                $url_params
            );
        } elseif (preg_match('/^whats-new$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'whats_new',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'whats_new', 
                $url_params
            );
        } elseif (preg_match('/^activities\\/users\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'activities',
                        'action' => [
                            'GET' => 'user',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'activities_user', 
                $url_params
            );
        } elseif (preg_match('/^activities\\/projects\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'activities',
                        'action' => [
                            'GET' => 'project',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'activities_project', 
                $url_params
            );
        } elseif (preg_match('/^activities$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'activities',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'global_activities', 
                $url_params
            );
        } elseif (preg_match('/^activity-logs\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'activity_log_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'activity_logs',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'activity_log', 
                $url_params,
                'ActivityLog',
                $url_params['activity_log_id'] ?? 0
            );
        } elseif (preg_match('/^activity-logs$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'activity_logs',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'activity_logs', 
                $url_params,
                'ActivityLogs'
            );
        } elseif (preg_match('/^shortcuts\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'shortcuts',
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reorder_shortcuts', 
                $url_params
            );
        } elseif (preg_match('/^shortcuts$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'shortcuts',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'create',
                            'DELETE' => 'batch_delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_shortcuts_collection', 
                $url_params
            );
        } elseif (preg_match('/^shortcuts\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'shortcut_id',
                    ],
                [
                        'controller' => 'shortcuts',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_shortcuts', 
                $url_params
            );
        } elseif (preg_match('/^user-conversations\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'user_conversations',
                        'action' => [
                            'PUT' => 'edit',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_conversation', 
                $url_params
            );
        } elseif (preg_match('/^user-conversations$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'user_conversations',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_conversations', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/attachments$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'attachments',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'conversation_attachments', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/messages\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                        'message_id',
                    ],
                [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'conversation_message', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/messages\\/create-task$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'POST' => 'create_task',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'conversation_messages_create_task', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/messages\\/(\\d+)\\/mark-as-unread$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                        'message_id',
                    ],
                [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'PUT' => 'mark_as_unread',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'conversation_messages_mark_as_unread', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/messages$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'conversation_messages',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'create',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'conversation_messages', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/admins\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                        'user_id',
                    ],
                [
                        'controller' => 'group_conversations',
                        'action' => [
                            'POST' => 'add_admin',
                            'DELETE' => 'remove_admin',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'group_conversation_admins', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/rename$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'group_conversations',
                        'action' => [
                            'PUT' => 'rename',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'group_conversation_rename', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/invite$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'group_conversations',
                        'action' => [
                            'POST' => 'invite',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'group_conversation_invite', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/remove\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                        'user_id',
                    ],
                [
                        'controller' => 'group_conversations',
                        'action' => [
                            'DELETE' => 'remove',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'group_conversation_remove', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)\\/leave$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'group_conversations',
                        'action' => [
                            'PUT' => 'leave',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'group_conversation_leave', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'conversation_id',
                    ],
                [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'view',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'conversation', 
                $url_params
            );
        } elseif (preg_match('/^conversations$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'conversations',
                        'action' => [
                            'POST' => 'create',
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'conversations', 
                $url_params
            );
        } elseif (preg_match('/^conversations\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'type',
                        'id',
                    ],
                [
                        'controller' => 'conversations',
                        'action' => [
                            'GET' => 'find',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'find-conversation', 
                $url_params
            );
        } elseif (preg_match('/^comments\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'comment_id',
                    ],
                [
                        'controller' => 'comments',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'comment', 
                $url_params
            );
        } elseif (preg_match('/^comments\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'controller' => 'comments',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'comments', 
                $url_params
            );
        } elseif (preg_match('/^logger\\/(\\w+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'log_level',
                    ],
                [
                        'controller' => 'logger',
                        'action' => [
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'logger', 
                $url_params
            );
        } elseif (preg_match('/^reactions\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'reaction_id',
                    ],
                [
                        'controller' => 'reactions',
                        'action' => [],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reaction', 
                $url_params
            );
        } elseif (preg_match('/^reactions\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'POST' => 'add_reaction',
                            'DELETE' => 'remove_reaction',
                        ],
                        'controller' => 'reactions',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reactions', 
                $url_params
            );
        } elseif (preg_match('/^cta-notifications\\/([a-z0-9\\-\\._]+)\\/dismiss$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'notification_type',
                    ],
                [
                        'controller' => 'cta_notifications',
                        'action' => [
                            'POST' => 'dismiss',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'cta_notifications_dismiss', 
                $url_params
            );
        } elseif (preg_match('/^cta-notifications\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'notification_type',
                    ],
                [
                        'controller' => 'cta_notifications',
                        'action' => [
                            'GET' => 'show',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'cta_notifications', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/wrike-importer\\/invite-users$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'GET' => 'invite_users',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'wrike_invite_users', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/wrike-importer\\/check-status$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'wrike_check_status', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/wrike-importer\\/start-over$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'wrike_start_over', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/wrike-importer\\/schedule-import$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'wrike_schedule_import', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/wrike-importer\\/authorize$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'wrike_importer_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'integration_type' => 'wrike-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'wrike_authorize', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/one-login\\/disable$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'one_login_integration',
                        'action' => [
                            'GET' => 'disable',
                        ],
                        'integration_type' => 'one-login',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'one_login_disable', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/one-login\\/enable$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'one_login_integration',
                        'action' => [
                            'GET' => 'enable',
                        ],
                        'integration_type' => 'one-login',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'one_login_enable', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/one-login\\/credentials$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'one_login_integration',
                        'action' => [
                            'POST' => 'credentials',
                        ],
                        'integration_type' => 'one-login',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'one_login_credentials', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/zapier\\/webhooks\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'zapier_webhook_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'zapier_webhooks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'zapier_webhook', 
                $url_params,
                'ZapierWebhook',
                $url_params['zapier_webhook_id'] ?? 0
            );
        } elseif (preg_match('/^integrations\\/zapier\\/webhooks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'zapier_webhooks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'zapier_webhooks', 
                $url_params,
                'ZapierWebhooks'
            );
        } elseif (preg_match('/^integrations\\/zapier\\/payload-examples\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'event_type',
                    ],
                [
                        'controller' => 'zapier_integration',
                        'action' => [
                            'GET' => 'payload_example',
                        ],
                        'integration_type' => 'zapier',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'zapier_integration_payload_example', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/zapier$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
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
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'zapier_integration', 
                $url_params
            );
        } elseif (preg_match('/^calendar-feeds\\/calendars\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'calendar_id',
                    ],
                [
                        'controller' => 'calendar_feeds',
                        'action' => [
                            'GET' => 'calendar',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'calendar_feeds_calendar', 
                $url_params
            );
        } elseif (preg_match('/^calendar-feeds\\/projects\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'calendar_feeds',
                        'action' => [
                            'GET' => 'project',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'calendar_feeds_project', 
                $url_params
            );
        } elseif (preg_match('/^calendar-feeds$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'calendar_feeds',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'calendar_feeds', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/webhooks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'webhooks_integration',
                        'action' => [
                            'POST' => 'add',
                        ],
                        'integration_type' => 'webhooks',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'webhooks_integration', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/webhooks\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'webhook_id',
                    ],
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
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'webhooks_integration_ids', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/slack\\/notification-channels\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'notification_channel_id',
                    ],
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
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'slack_notification_channel', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/slack\\/connect$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'slack_integration',
                        'action' => [
                            'PUT' => 'connect',
                        ],
                        'integration_type' => 'slack',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'slack_connect', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/sample-projects$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'sample_projects_integration',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'import',
                        ],
                        'integration_type' => 'sample-projects',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'sample_projects', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/asana-importer\\/invite-users$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'GET' => 'invite_users',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'asana_invite_users', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/asana-importer\\/check-status$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'asana_check_status', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/asana-importer\\/start-over$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'asana_start_over', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/asana-importer\\/schedule-import$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'asana_schedule_import', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/asana-importer\\/authorize$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'asana_authorize', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/asana-importer\\/request-url$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'asana_importer_integration',
                        'action' => [
                            'GET' => 'get_request_url',
                        ],
                        'integration_type' => 'asana-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'asana_request_url', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/trello-importer\\/invite-users$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'GET' => 'invite_users',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'trello_invite_users', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/trello-importer\\/check-status$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'trello_check_status', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/trello-importer\\/start-over$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'trello_start_over', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/trello-importer\\/schedule-import$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'trello_schedule_import', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/trello-importer\\/authorize$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'PUT' => 'authorize',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'trello_authorize', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/trello-importer\\/request-url$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'trello_importer_integration',
                        'action' => [
                            'GET' => 'get_request_url',
                        ],
                        'integration_type' => 'trello-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'trello_request_url', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/client-plus$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'client_plus_integration',
                        'action' => [
                            'POST' => 'activate',
                            'DELETE' => 'deactivate',
                        ],
                        'integration_type' => 'client_plus',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'client_plus', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/basecamp-importer\\/invite-users$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'invite_users',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'basecamp_invite_users', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/basecamp-importer\\/check-status$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'GET' => 'check_status',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'basecamp_check_status', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/basecamp-importer\\/start-over$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'start_over',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'basecamp_start_over', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/basecamp-importer\\/schedule-import$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'schedule_import',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'basecamp_schedule_import', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/basecamp-importer\\/check-credentials$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'basecamp_importer_integration',
                        'action' => [
                            'POST' => 'check_credentials',
                        ],
                        'integration_type' => 'basecamp-importer',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'basecamp_check_credentials', 
                $url_params
            );
        } elseif (preg_match('/^system\\/versions\\/old-versions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'versions',
                        'action' => [
                            'GET' => 'check_old_versions',
                            'DELETE' => 'delete_old_versions',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'versions', 
                $url_params
            );
        } elseif (preg_match('/^security$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'security',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'security', 
                $url_params
            );
        } elseif (preg_match('/^maintenance-mode$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'maintenance_mode',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'maintenance_mode', 
                $url_params
            );
        } elseif (preg_match('/^new-features$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'new_features',
                        'action' => [
                            'GET' => 'list_new_features',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'new_features', 
                $url_params
            );
        } elseif (preg_match('/^feedback\\/check$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'feedback',
                        'action' => [
                            'GET' => 'check',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'feedback_check', 
                $url_params
            );
        } elseif (preg_match('/^feedback$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'feedback',
                        'action' => [
                            'POST' => 'send',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'feedback', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/projects\\/ids$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'project_ids',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_project_ids', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/projects$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'projects',
                            'POST' => 'add_to_projects',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_projects', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/with-people-permissions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'with_people_permissions',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_with_people_permissions', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/dependencies\\/tasks\\/([a-z0-9\\-\\._]+)\\/suggestions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'task_id',
                    ],
                [
                        'controller' => 'project_template_task_dependencies',
                        'action' => [
                            'GET' => 'dependency_suggestions',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_template_task_dependencies_suggestions', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/dependencies\\/tasks\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'task_id',
                    ],
                [
                        'controller' => 'project_template_task_dependencies',
                        'action' => [
                            'GET' => 'view',
                            'POST' => 'create',
                            'PUT' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_template_task_dependencies', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/([a-z0-9\\-\\._]+)\\/elements\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'project_template_elements',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_template_elements_reorder', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/([a-z0-9\\-\\._]+)\\/elements\\/download$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                    ],
                [
                        'action' => [
                            'GET' => 'download',
                        ],
                        'controller' => 'project_template_elements',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_template_element_download', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/([a-z0-9\\-\\._]+)\\/elements\\/batch$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                    ],
                [
                        'action' => [
                            'POST' => 'batch_add',
                        ],
                        'controller' => 'project_template_elements',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_template_elements_batch', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/([a-z0-9\\-\\._]+)\\/elements\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                        'project_template_element_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'project_template_elements',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'project_template_element', 
                $url_params,
                'ProjectTemplateElement',
                $url_params['project_template_element_id'] ?? 0
            );
        } elseif (preg_match('/^project-templates\\/([a-z0-9\\-\\._]+)\\/elements$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'project_template_elements',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'project_template_elements', 
                $url_params,
                'ProjectTemplateElements'
            );
        } elseif (preg_match('/^project-templates\\/([a-z0-9\\-\\._]+)\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                    ],
                [
                        'controller' => 'project_templates',
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_template_reorder', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/([a-z0-9\\-\\._]+)\\/duplicate$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                    ],
                [
                        'controller' => 'project_templates',
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_template_duplicate', 
                $url_params
            );
        } elseif (preg_match('/^project-templates\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_template_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'project_templates',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'project_template', 
                $url_params,
                'ProjectTemplate',
                $url_params['project_template_id'] ?? 0
            );
        } elseif (preg_match('/^project-templates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'project_templates',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'project_templates', 
                $url_params,
                'ProjectTemplates'
            );
        } elseif (preg_match('/^labels\\/task-labels$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'task_labels',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_labels', 
                $url_params
            );
        } elseif (preg_match('/^labels\\/project-labels$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'project_labels',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_labels', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/expenses\\/(\\d+)\\/move$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'expense_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'expenses',
                        'action' => [
                            'PUT' => 'move',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'expense_move', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/expenses\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'expense_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'expenses',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'expense', 
                $url_params,
                'Expense',
                $url_params['expense_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/expenses$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'expenses',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'expenses', 
                $url_params,
                'Expenses'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/time-records\\/(\\d+)\\/move$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'time_record_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'PUT' => 'move',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'time_record_move', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/time-records\\/filtered-by-date$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'GET' => 'filtered_by_date',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'time_records_filtered_by_date', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/time-records\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'time_record_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'time_record', 
                $url_params,
                'TimeRecord',
                $url_params['time_record_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/time-records$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'tracking',
                        'controller' => 'time_records',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'time_records', 
                $url_params,
                'TimeRecords'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/note-groups\\/(\\d+)\\/move-to-group$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_group_id',
                    ],
                [
                        'controller' => 'note_groups',
                        'action' => [
                            'PUT' => 'move_to_group',
                        ],
                        'module' => 'notes',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'note_group_move_to_group', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/note-groups\\/(\\d+)\\/reorder-notes$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_group_id',
                    ],
                [
                        'controller' => 'note_groups',
                        'action' => [
                            'PUT' => 'reorder_notes',
                        ],
                        'module' => 'notes',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'note_group_reorder_notes', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/note-groups\\/(\\d+)\\/notes$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_group_id',
                    ],
                [
                        'controller' => 'note_groups',
                        'action' => [
                            'GET' => 'notes',
                        ],
                        'module' => 'notes',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'note_group_notes', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/note-groups\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_group_id',
                    ],
                [
                        'module' => 'notes',
                        'controller' => 'note_groups',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'note_group', 
                $url_params,
                'NoteGroup',
                $url_params['note_group_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/note-groups$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'notes',
                        'controller' => 'note_groups',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'note_groups', 
                $url_params,
                'NoteGroups'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/notes\\/(\\d+)\\/versions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_id',
                    ],
                [
                        'action' => [
                            'GET' => 'versions',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'note_versions', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/notes\\/(\\d+)\\/move-to-project$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'note_move_to_project', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/notes\\/(\\d+)\\/move-to-group$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'move_to_group',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'note_move_to_group', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/notes\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'notes',
                        'module' => 'notes',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'notes_reorder', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/notes\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'note_id',
                    ],
                [
                        'module' => 'notes',
                        'controller' => 'notes',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'note', 
                $url_params,
                'Note',
                $url_params['note_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/notes$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'notes',
                        'controller' => 'notes',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'notes', 
                $url_params,
                'Notes'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/files\\/(\\d+)\\/move-to-project$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'file_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'files',
                        'module' => 'files',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'file_move_to_project', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/files\\/(\\d+)\\/download$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'file_id',
                    ],
                [
                        'action' => [
                            'GET' => 'download',
                        ],
                        'controller' => 'files',
                        'module' => 'files',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'file_download', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/files\\/batch$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'GET' => 'batch_download',
                            'POST' => 'batch_add',
                        ],
                        'controller' => 'files',
                        'module' => 'files',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'files_batch', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/files\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'file_id',
                    ],
                [
                        'module' => 'files',
                        'controller' => 'files',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'file', 
                $url_params,
                'File',
                $url_params['file_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/files$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'files',
                        'controller' => 'files',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'files', 
                $url_params,
                'Files'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/discussions\\/(\\d+)\\/promote-to-task$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'discussion_id',
                    ],
                [
                        'action' => [
                            'POST' => 'promote_to_task',
                        ],
                        'controller' => 'discussions',
                        'module' => 'discussions',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'discussion_promote_to_task', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/discussions\\/(\\d+)\\/move-to-project$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'discussion_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'discussions',
                        'module' => 'discussions',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'discussion_move_to_project', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/discussions\\/read-status$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'GET' => 'read_status',
                        ],
                        'controller' => 'discussions',
                        'module' => 'discussions',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'discussions_read_status', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/discussions\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'discussion_id',
                    ],
                [
                        'module' => 'discussions',
                        'controller' => 'discussions',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'discussion', 
                $url_params,
                'Discussion',
                $url_params['discussion_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/discussions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'discussions',
                        'controller' => 'discussions',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'discussions', 
                $url_params,
                'Discussions'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/recurring-tasks\\/(\\d+)\\/create-task$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'recurring_task_id',
                    ],
                [
                        'action' => [
                            'POST' => 'create_task',
                        ],
                        'controller' => 'recurring_tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'recurring_task_create_task', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/recurring-tasks\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'recurring_task_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'recurring_tasks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'recurring_task', 
                $url_params,
                'RecurringTask',
                $url_params['recurring_task_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/recurring-tasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'recurring_tasks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'recurring_tasks', 
                $url_params,
                'RecurringTasks'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/(\\d+)\\/duplicate$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_duplicate', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/(\\d+)\\/move-to-project$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_move_to_project', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/([a-z0-9\\-\\._]+)\\/subtasks\\/(\\d+)\\/promote-to-task$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                        'subtask_id',
                    ],
                [
                        'action' => [
                            'POST' => 'promote_to_task',
                        ],
                        'controller' => 'subtasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'subtask_promote_to_task', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/([a-z0-9\\-\\._]+)\\/subtasks\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'subtasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'subtasks_reorder', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/([a-z0-9\\-\\._]+)\\/subtasks\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                        'subtask_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'subtasks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'subtask', 
                $url_params,
                'Subtask',
                $url_params['subtask_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/([a-z0-9\\-\\._]+)\\/subtasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'subtasks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'subtasks', 
                $url_params,
                'Subtasks'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/(\\d+)\\/expenses$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'action' => [
                            'GET' => 'expenses',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_expenses', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/(\\d+)\\/time-records\\/info$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'action' => [
                            'GET' => 'time_records_info',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_time_records_info', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/(\\d+)\\/time-records$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'action' => [
                            'GET' => 'time_records',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_time_records', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/for-screen$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'GET' => 'for_screen',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'tasks_for_screen', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'tasks_reorder', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'controller' => 'tasks',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'tasks_archive', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'tasks',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'task', 
                $url_params,
                'Task',
                $url_params['task_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/tasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'tasks',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                            'PUT' => 'batch_update',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'tasks', 
                $url_params,
                'Tasks'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/insert$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'POST' => 'insert',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_lists_insert', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/(\\d+)\\/completed-tasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_list_id',
                    ],
                [
                        'action' => [
                            'GET' => 'completed_tasks',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_list_completed_tasks', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/(\\d+)\\/tasks$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_list_id',
                    ],
                [
                        'action' => [
                            'GET' => 'open_tasks',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_list_open_tasks', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/(\\d+)\\/duplicate$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_list_id',
                    ],
                [
                        'action' => [
                            'POST' => 'duplicate',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_list_duplicate', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/(\\d+)\\/move-to-project$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_list_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'move_to_project',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_list_move_to_project', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_lists_reorder', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_lists_archive', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/all$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'action' => [
                            'GET' => 'all_task_lists',
                        ],
                        'controller' => 'task_lists',
                        'module' => 'tasks',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'task_lists_all_task_lists', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'task_list_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'task_lists',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'task_list', 
                $url_params,
                'TaskList',
                $url_params['task_list_id'] ?? 0
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/task-lists$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'tasks',
                        'controller' => 'task_lists',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'task_lists', 
                $url_params,
                'TaskLists'
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/applied-templates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'applied_templates',
                        'action' => [
                            'GET' => 'applied_templates',
                            'POST' => 'apply_template',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_applied_templates', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/responsibilities$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'project_members',
                        'action' => [
                            'GET' => 'responsibilities',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_responsibilities', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/revoke-client-access$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'project_members',
                        'action' => [
                            'PUT' => 'revoke_client_access',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_revoke_client_access', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/(\\d+)\\/members\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                        'user_id',
                    ],
                [
                        'controller' => 'project_members',
                        'action' => [
                            'PUT' => 'replace',
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_member', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/members$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'project_members',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_members', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/(\\d+)\\/additional-data$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'additional_data',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_additional_data', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/(\\d+)\\/export$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_export', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/(\\d+)\\/budget$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'budget',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_budget', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/(\\d+)\\/whats-new$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'whats_new',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'project_whats_new', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/batch-update-budget-types$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'PUT' => 'batch_update_budget_types',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_batch_update_budget_types', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/budgeting-data$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'budgeting_data',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_budgeting_data', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/([a-z0-9\\-\\._]+)\\/financial-stats$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'financial_stats',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_financial_stats', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/categories$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'categories',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_categories', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/calendar-events$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'calendar_events',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_calendar_events', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/labels$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'labels',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_labels', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/with-tracking-enabled$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'with_tracking_enabled',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_with_tracking_enabled', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/for-screen$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'for_screen',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_for_screen', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/names$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'names',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_names', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/archive-filters$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'filters_for_archived',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_archive_filters', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_archive', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/filter$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'filter',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'projects_filter', 
                $url_params
            );
        } elseif (preg_match('/^projects\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'project_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'project', 
                $url_params,
                'Project',
                $url_params['project_id'] ?? 0
            );
        } elseif (preg_match('/^projects$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'projects',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'projects', 
                $url_params,
                'Projects'
            );
        } elseif (preg_match('/^categories\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'category_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'categories',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'category', 
                $url_params,
                'Category',
                $url_params['category_id'] ?? 0
            );
        } elseif (preg_match('/^categories$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'categories',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'categories', 
                $url_params,
                'Categories'
            );
        } elseif (preg_match('/^teams\\/(\\d+)\\/members\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'team_id',
                        'user_id',
                    ],
                [
                        'controller' => 'team_members',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'team_member', 
                $url_params
            );
        } elseif (preg_match('/^teams\\/([a-z0-9\\-\\._]+)\\/members$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'team_id',
                    ],
                [
                        'controller' => 'team_members',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'team_members', 
                $url_params
            );
        } elseif (preg_match('/^teams\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'team_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'teams',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'team', 
                $url_params,
                'Team',
                $url_params['team_id'] ?? 0
            );
        } elseif (preg_match('/^teams$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'teams',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'teams', 
                $url_params,
                'Teams'
            );
        } elseif (preg_match('/^companies\\/(\\d+)\\/invoices$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'company_id',
                    ],
                [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'invoices',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'company_invoices', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/(\\d+)\\/project-names$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'company_id',
                    ],
                [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'project_names',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'company_project_names', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/(\\d+)\\/projects$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'company_id',
                    ],
                [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'projects',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'company_projects', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/(\\d+)\\/export$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'company_id',
                    ],
                [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'company_export', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/notes$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'notes',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'companies_notes', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'archive',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'companies_archive', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/all$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'all',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'companies_all', 
                $url_params
            );
        } elseif (preg_match('/^companies\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'company_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'company', 
                $url_params,
                'Company',
                $url_params['company_id'] ?? 0
            );
        } elseif (preg_match('/^companies$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'companies',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'companies', 
                $url_params,
                'Companies'
            );
        } elseif (preg_match('/^socket\\/auth$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'socket_auth',
                        'action' => [
                            'POST' => 'authenticate',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'socket_auth', 
                $url_params
            );
        } elseif (preg_match('/^password-recovery\\/reset-password$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'password_recovery',
                        'action' => [
                            'POST' => 'reset_password',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'password_recovery_reset_password', 
                $url_params
            );
        } elseif (preg_match('/^password-recovery\\/send-code$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'password_recovery',
                        'action' => [
                            'POST' => 'send_code',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'password_recovery_send_code', 
                $url_params
            );
        } elseif (preg_match('/^accept-invitation$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'user_session',
                        'action' => [
                            'GET' => 'view_invitation',
                            'POST' => 'accept_invitation',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'accept_invitation', 
                $url_params
            );
        } elseif (preg_match('/^issue-token-intent$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'user_session',
                        'action' => [
                            'POST' => 'issue_token',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'issue_token_by_intent', 
                $url_params
            );
        } elseif (preg_match('/^issue-token$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'user_session',
                        'action' => [
                            'POST' => 'issue_token',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'issue_token', 
                $url_params
            );
        } elseif (preg_match('/^issue-file-access-token$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'user_session',
                        'action' => [
                            'GET' => 'issue_file_access_token',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'issue_file_access_token', 
                $url_params
            );
        } elseif (preg_match('/^authorize-file-access$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'authorize_file_access',
                        'action' => [
                            'POST' => 'authorize',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'authorize_file_access', 
                $url_params
            );
        } elseif (preg_match('/^user-session$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'user_session',
                        'action' => [
                            'GET' => 'who_am_i',
                            'POST' => 'login',
                            'DELETE' => 'logout',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_session', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/workspaces$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'user_workspaces',
                        'action' => [
                            'GET' => 'index',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_workspaces', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/api-subscriptions\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                        'api_subscription_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'api_subscriptions',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'api_subscription', 
                $url_params,
                'ApiSubscription',
                $url_params['api_subscription_id'] ?? 0
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/api-subscriptions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'api_subscriptions',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'api_subscriptions', 
                $url_params,
                'ApiSubscriptions'
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/sessions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'user_sessions',
                        'action' => [
                            'GET' => 'index',
                            'DELETE' => 'remove',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_sessions', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/job-types$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'job_types',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_job_types', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/password-permissions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'password_permissions',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_password_permissions', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/profile-permissions$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'profile_permissions',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_profile_permissions', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/change-daily-capacity$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_daily_capacity',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_change_daily_capacity', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/change-role$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_role',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_change_role', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/clear-avatar$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'DELETE' => 'clear_avatar',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_clear_avatar', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/activities$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'activities',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_activities', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/export$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'export',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_export', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/get-invitation\\/accept-url$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'get_accept_invitation_url',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_get_accept_invitation_url', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/get-invitation$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'get_invitation',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_get_invitation', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/resend-invitation$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'resend_invitation',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_resend_invitation', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/change-user-profile$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_user_profile',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_change_user_profile', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/change-user-password$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_user_password',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_change_user_password', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/change-password$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'change_password',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_change_password', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/reactivate$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'reactivate',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_reactivate', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'PUT' => 'move_to_archive',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'user_archive', 
                $url_params
            );
        } elseif (preg_match('/^users\\/check-email$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'check_email',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'users_check_email', 
                $url_params
            );
        } elseif (preg_match('/^users\\/archive$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'archive',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'users_archive', 
                $url_params
            );
        } elseif (preg_match('/^users\\/all$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'all',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'users_all', 
                $url_params
            );
        } elseif (preg_match('/^users\\/invite$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'POST' => 'invite',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'users_invite', 
                $url_params
            );
        } elseif (preg_match('/^users\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'user_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'user', 
                $url_params,
                'User',
                $url_params['user_id'] ?? 0
            );
        } elseif (preg_match('/^users$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'users',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'users', 
                $url_params,
                'Users'
            );
        } elseif (preg_match('/^calendars\\/([a-z0-9\\-\\._]+)\\/events\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'calendar_id',
                        'calendar_event_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'calendar_events',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'calendar_event', 
                $url_params,
                'CalendarEvent',
                $url_params['calendar_event_id'] ?? 0
            );
        } elseif (preg_match('/^calendars\\/([a-z0-9\\-\\._]+)\\/events$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'calendar_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'calendar_events',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'calendar_events', 
                $url_params,
                'CalendarEvents'
            );
        } elseif (preg_match('/^calendars\\/events$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'calendars',
                        'action' => [
                            'GET' => 'all_calendar_events',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'calendars_events', 
                $url_params
            );
        } elseif (preg_match('/^calendars\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'calendar_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'calendars',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'calendar', 
                $url_params,
                'Calendar',
                $url_params['calendar_id'] ?? 0
            );
        } elseif (preg_match('/^calendars$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'calendars',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'calendars', 
                $url_params,
                'Calendars'
            );
        } elseif (preg_match('/^reminders\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'reminder_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'reminders',
                        'action' => [
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reminder', 
                $url_params
            );
        } elseif (preg_match('/^reminders\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'reminders',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reminders', 
                $url_params
            );
        } elseif (preg_match('/^public-payments\\/process-stripe-confirmation-intent$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'public_payments',
                        'action' => [
                            'POST' => 'process_stripe_confirmation_intent',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'payment_process_credit_card', 
                $url_params
            );
        } elseif (preg_match('/^payment-gateways\\/clear-credit-card$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'payment_gateways',
                        'action' => [
                            'DELETE' => 'clear_credit_card',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'payment_gateway_clear_credit_card', 
                $url_params
            );
        } elseif (preg_match('/^payment-gateways\\/clear-paypal$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'payment_gateways',
                        'action' => [
                            'DELETE' => 'clear_paypal',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'payment_gateway_clear_paypal', 
                $url_params
            );
        } elseif (preg_match('/^payment-gateways$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'payment_gateways',
                        'action' => [
                            'GET' => 'get_settings',
                            'PUT' => 'update_settings',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'payment_gateways', 
                $url_params
            );
        } elseif (preg_match('/^public_payments\\/authorizenet-form$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'public_payments',
                        'action' => [
                            'GET' => 'authorizenet_form',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'public_payment_authorizenet_form', 
                $url_params
            );
        } elseif (preg_match('/^public_payments\\/authorizenet-confirm$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'public_payments',
                        'action' => [
                            'GET' => 'authorizenet_confirm',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'public_payment_authorizenet_confirm', 
                $url_params
            );
        } elseif (preg_match('/^public_payments$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
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
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'public_payments', 
                $url_params
            );
        } elseif (preg_match('/^payments\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'payment_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'payments',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'payment', 
                $url_params,
                'Payment',
                $url_params['payment_id'] ?? 0
            );
        } elseif (preg_match('/^payments$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'payments',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'payments', 
                $url_params,
                'Payments'
            );
        } elseif (preg_match('/^labels\\/(\\d+)\\/set-as-default$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'label_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'set_as_default',
                        ],
                        'controller' => 'labels',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'label_set_as_default', 
                $url_params
            );
        } elseif (preg_match('/^labels\\/reorder$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'PUT' => 'reorder',
                        ],
                        'controller' => 'labels',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'labels_reorder', 
                $url_params
            );
        } elseif (preg_match('/^labels\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'label_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'label', 
                $url_params,
                'Label',
                $url_params['label_id'] ?? 0
            );
        } elseif (preg_match('/^labels$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'labels',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'labels', 
                $url_params,
                'Labels'
            );
        } elseif (preg_match('/^subscribers\\/([a-z0-9\\-\\._]+)\\/(\\d+)\\/users\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                        'user_id',
                    ],
                [
                        'action' => [
                            'POST' => 'subscribe',
                            'DELETE' => 'unsubscribe',
                        ],
                        'controller' => 'subscribers',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'subscriber', 
                $url_params
            );
        } elseif (preg_match('/^subscribers\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
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
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'subscribers', 
                $url_params
            );
        } elseif (preg_match('/^public\\/notifications\\/unsubscribe$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'public_notifications',
                        'action' => 'unsubscribe',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'public_notifications_unsubscribe', 
                $url_params
            );
        } elseif (preg_match('/^public\\/notifications\\/subscribe$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'public_notifications',
                        'action' => 'subscribe',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'public_notifications_subscribe', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/push$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'notifications',
                        'action' => [
                            'POST' => 'push_notification_subscribe',
                            'PUT' => 'push_notification_unsubscribe',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'push_notification_subscribe_actions', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/mark-all-as-read$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'notifications',
                        'action' => [
                            'PUT' => 'mark_all_as_read',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'notifications_mark_all_as_read', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/object-updates\\/recent$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'recent_object_updates',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'notifications_recent_object_updates', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/object-updates\\/unread-count$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'object_updates_unread_count',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'notifications_object_updates_unread_count', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/object-updates\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'notification_id',
                    ],
                [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'notification_object_updates',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'notifications_object_objets_notification', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/object-updates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'object_updates',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'notifications_object_updates', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/unread$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'unread',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'notifications_unread', 
                $url_params
            );
        } elseif (preg_match('/^notifications\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'notification_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'notification', 
                $url_params,
                'Notification',
                $url_params['notification_id'] ?? 0
            );
        } elseif (preg_match('/^notifications$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'notifications',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'notifications', 
                $url_params,
                'Notifications'
            );
        } elseif (preg_match('/^attachments\\/([a-z0-9\\-\\._]+)\\/([a-z0-9\\-\\._]+)\\/download$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'controller' => 'attachments_archive',
                        'action' => [
                            'POST' => 'prepare',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'attachments_batch_download', 
                $url_params
            );
        } elseif (preg_match('/^attachments\\/(\\d+)\\/download$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'attachment_id',
                    ],
                [
                        'controller' => 'attachments',
                        'action' => [
                            'GET' => 'download',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'attachment_download', 
                $url_params
            );
        } elseif (preg_match('/^attachments\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'attachment_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'attachments',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'attachment', 
                $url_params,
                'Attachment',
                $url_params['attachment_id'] ?? 0
            );
        } elseif (preg_match('/^attachments$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'attachments',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'attachments', 
                $url_params,
                'Attachments'
            );
        } elseif (preg_match('/^integrations\\/email\\/test-connection$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'email_integration',
                        'action' => [
                            'POST' => 'test_connection',
                        ],
                        'integration_type' => 'email',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'email_integration_test_connection', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/email\\/email-log$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'email_integration',
                        'action' => [
                            'GET' => 'email_log',
                        ],
                        'integration_type' => 'email',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'email_integration_email_log', 
                $url_params
            );
        } elseif (preg_match('/^history\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'GET' => 'index',
                        ],
                        'controller' => 'history',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'history', 
                $url_params
            );
        } elseif (preg_match('/^since-last-visit\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'GET' => 'index',
                        ],
                        'controller' => 'since_last_visit',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'since_last_visit', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/dropbox\\/batch$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'dropbox',
                        'action' => [
                            'POST' => 'batch_add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'dropbox_batch', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/google-drive\\/batch$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'google_drive',
                        'action' => [
                            'POST' => 'batch_add',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'google_drive_batch', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/warehouse\\/store\\/export$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'warehouse',
                        'action' => [
                            'POST' => 'store_export_pingback',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'warehouse_store_export_complete_pingback', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/warehouse\\/pingback$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'warehouse',
                        'action' => [
                            'POST' => 'pingback',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'warehouse_pingback', 
                $url_params
            );
        } elseif (preg_match('/^favorites\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'GET' => 'check',
                            'PUT' => 'add',
                            'DELETE' => 'remove',
                        ],
                        'controller' => 'favorites',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'favorite', 
                $url_params
            );
        } elseif (preg_match('/^favorites$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'GET' => 'index',
                        ],
                        'controller' => 'favorites',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'favorites', 
                $url_params
            );
        } elseif (preg_match('/^data-filters\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'data_filter_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'data_filters',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'data_filter', 
                $url_params,
                'DataFilter',
                $url_params['data_filter_id'] ?? 0
            );
        } elseif (preg_match('/^data-filters$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'data_filters',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'data_filters', 
                $url_params,
                'DataFilters'
            );
        } elseif (preg_match('/^day-offs\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'day_off_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'day_offs',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'day_off', 
                $url_params,
                'DayOff',
                $url_params['day_off_id'] ?? 0
            );
        } elseif (preg_match('/^day-offs$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'day_offs',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'day_offs', 
                $url_params,
                'DayOffs'
            );
        } elseif (preg_match('/^reports\\/export$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'reports',
                        'action' => [
                            'GET' => 'export',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reports_export', 
                $url_params
            );
        } elseif (preg_match('/^reports\\/run$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'reports',
                        'action' => [
                            'GET' => 'run',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reports_run', 
                $url_params
            );
        } elseif (preg_match('/^reports$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'reports',
                        'action' => [
                            'GET' => 'index',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reports', 
                $url_params
            );
        } elseif (preg_match('/^workweek$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'workweek',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'workweek', 
                $url_params
            );
        } elseif (preg_match('/^currencies\\/default$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'currencies',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'currencies_default', 
                $url_params
            );
        } elseif (preg_match('/^currencies\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'currency_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'currencies',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'currency', 
                $url_params,
                'Currency',
                $url_params['currency_id'] ?? 0
            );
        } elseif (preg_match('/^currencies$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'currencies',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'currencies', 
                $url_params,
                'Currencies'
            );
        } elseif (preg_match('/^personalized-config-options$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'GET' => 'personalized_get',
                            'PUT' => 'personalized_set',
                        ],
                        'controller' => 'config_options',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'personalized_config_options', 
                $url_params
            );
        } elseif (preg_match('/^config-options$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'GET' => 'get',
                            'PUT' => 'set',
                        ],
                        'controller' => 'config_options',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'config_options', 
                $url_params
            );
        } elseif (preg_match('/^localization\\/states$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_states',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'localization_states', 
                $url_params
            );
        } elseif (preg_match('/^localization\\/eu-countries$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_eu_countries',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'localization_eu_countries', 
                $url_params
            );
        } elseif (preg_match('/^localization\\/countries$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_countries',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'localization_countries', 
                $url_params
            );
        } elseif (preg_match('/^localization\\/time-formats$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_time_formats',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'localization_time_formats', 
                $url_params
            );
        } elseif (preg_match('/^localization\\/date-formats$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_date_formats',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'localization_date_formats', 
                $url_params
            );
        } elseif (preg_match('/^localization\\/timezones$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_timezones',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'localization_timezones', 
                $url_params
            );
        } elseif (preg_match('/^localization$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'localization',
                        'action' => [
                            'GET' => 'show_settings',
                            'PUT' => 'save_settings',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'localization', 
                $url_params
            );
        } elseif (preg_match('/^languages\\/default$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'languages',
                        'action' => [
                            'GET' => 'view_default',
                            'PUT' => 'set_default',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'languages_default', 
                $url_params
            );
        } elseif (preg_match('/^languages\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'language_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'languages',
                        'action' => [
                            'GET' => 'view',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'language', 
                $url_params,
                'Language',
                $url_params['language_id'] ?? 0
            );
        } elseif (preg_match('/^languages$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'languages',
                        'action' => [
                            'GET' => 'index',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'languages', 
                $url_params,
                'Languages'
            );
        } elseif (preg_match('/^integrations\\/search\\/disconnect$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'search_integration',
                        'action' => [
                            'POST' => 'disconnect',
                        ],
                        'integration_type' => 'search',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'search_integration_disconnect', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/search\\/test-connection$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'search_integration',
                        'action' => [
                            'POST' => 'test_connection',
                        ],
                        'integration_type' => 'search',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'search_integration_test_connection', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/search\\/configure$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'search_integration',
                        'action' => [
                            'PUT' => 'configure',
                        ],
                        'integration_type' => 'search',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'search_integration_configure', 
                $url_params
            );
        } elseif (preg_match('/^search$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'GET' => 'query',
                        ],
                        'controller' => 'search',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'search', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/cron$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'cron_integration',
                        'action' => 'get',
                        'integration_type' => 'cron',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'cron_integration', 
                $url_params
            );
        } elseif (preg_match('/^integrations\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'integration_id',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'integrations',
                        'action' => [
                            'GET' => 'view',
                            'PUT' => 'edit',
                            'DELETE' => 'delete',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedEntity(
                'integration', 
                $url_params,
                'Integration',
                $url_params['integration_id'] ?? 0
            );
        } elseif (preg_match('/^integrations$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'module' => 'system',
                        'controller' => 'integrations',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'add',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedCollection(
                'integrations', 
                $url_params,
                'Integrations'
            );
        } elseif (preg_match('/^integrations\\/([a-z0-9\\-\\._]+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'integration_type',
                    ],
                [
                        'module' => 'system',
                        'controller' => 'integration_singletons',
                        'action' => [
                            'GET' => 'get',
                            'PUT' => 'set',
                            'DELETE' => 'forget',
                        ],
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'integration_singletons', 
                $url_params
            );
        } elseif (preg_match('/^upload-files$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'POST' => 'index',
                            'GET' => 'prepare',
                        ],
                        'controller' => 'upload_files',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'upload_files', 
                $url_params
            );
        } elseif (preg_match('/^access-logs\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'GET' => 'index',
                            'PUT' => 'log_access',
                        ],
                        'controller' => 'access_logs',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'access_logs', 
                $url_params
            );
        } elseif (preg_match('/^compare-text$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'POST' => 'compare',
                        ],
                        'controller' => 'compare_text',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'compare_text', 
                $url_params
            );
        } elseif (preg_match('/^open\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'open',
                        ],
                        'controller' => 'complete',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'open', 
                $url_params
            );
        } elseif (preg_match('/^complete\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'complete',
                        ],
                        'controller' => 'complete',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'complete', 
                $url_params
            );
        } elseif (preg_match('/^reactivate\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'reactivate',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'reactivate', 
                $url_params
            );
        } elseif (preg_match('/^permanently-delete\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'DELETE' => 'permanently_delete',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'permanently_delete', 
                $url_params
            );
        } elseif (preg_match('/^restore-from-trash\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'restore_from_trash',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'restore_from_trash', 
                $url_params
            );
        } elseif (preg_match('/^move-to-trash\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'trash',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'move_to_trash', 
                $url_params
            );
        } elseif (preg_match('/^restore-from-archive\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'restore_from_archive',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'restore_from_archive', 
                $url_params
            );
        } elseif (preg_match('/^move-to-archive\\/([a-z0-9\\-\\._]+)\\/(\\d+)$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'parent_type',
                        'parent_id',
                    ],
                [
                        'action' => [
                            'PUT' => 'archive',
                        ],
                        'controller' => 'state',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'move_to_archive', 
                $url_params
            );
        } elseif (preg_match('/^trash$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'action' => [
                            'GET' => 'show_content',
                            'DELETE' => 'empty_trash',
                        ],
                        'controller' => 'trash',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'trash', 
                $url_params
            );
        } elseif (preg_match('/^system-notifications\\/([a-z0-9\\-\\._]+)\\/dismiss$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [
                        'notification_id',
                    ],
                [
                        'controller' => 'system_notifications',
                        'action' => [
                            'GET' => 'dismiss',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'system_notifications_dismiss', 
                $url_params
            );
        } elseif (preg_match('/^upgrade\\/release-notes$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'upgrade',
                        'action' => 'release_notes',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'upgrade_release_notes', 
                $url_params
            );
        } elseif (preg_match('/^upgrade$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'upgrade',
                        'action' => [
                            'GET' => 'index',
                            'POST' => 'finish',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'upgrade', 
                $url_params
            );
        } elseif (preg_match('/^system-status\\/check-environment$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'system_status',
                        'action' => 'check_environment',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'check_environment', 
                $url_params
            );
        } elseif (preg_match('/^system-status\\/download-release$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'system_status',
                        'action' => [
                            'GET' => 'get_download_progress',
                            'POST' => 'start_download',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'download_release', 
                $url_params
            );
        } elseif (preg_match('/^system-status\\/check-for-updates$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'system_status',
                        'action' => 'check_for_updates',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'check_for_updates', 
                $url_params
            );
        } elseif (preg_match('/^system-status$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'system_status',
                        'action' => 'index',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'system_status', 
                $url_params
            );
        } elseif (preg_match('/^initial\\/test-speed$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'initial',
                        'action' => 'test_action_speed',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'initial_speed_test', 
                $url_params
            );
        } elseif (preg_match('/^initial$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'initial',
                        'action' => 'index',
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'initial', 
                $url_params
            );
        } elseif (preg_match('/^info$/', $path, $matches)) {
            $url_params = $this->valuesFromMatchedPath(
                [],
                [
                        'controller' => 'utilities',
                        'action' => [
                            'GET' => 'info',
                        ],
                        'module' => 'system',
                    ],
                $matches,
                $query_string
            );

            return new MatchedRoute(
                'api_info', 
                $url_params
            );
        }

        return null;
    }
}
