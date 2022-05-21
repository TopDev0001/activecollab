<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;

/**
 * Report that filters time records and expenses.
 */
class TrackingFilter extends DataFilter implements IInvoiceBasedOn
{
    use IInvoiceBasedOnTrackingFilterResultImplementation;

    // Billable filter
    const BILLABLE_FILTER_ALL = 'all';
    const BILLABLE_FILTER_BILLABLE = 'billable';
    const BILLABLE_FILTER_NOT_BILLABLE = 'not_billable';
    const BILLABLE_FILTER_BILLABLE_PAID = 'billable_paid';
    const BILLABLE_FILTER_BILLABLE_NOT_PAID = 'billable_not_paid';
    const BILLABLE_FILTER_BILLABLE_PENDING_OR_PAID = 'billable_pending_or_paid';
    const BILLABLE_FILTER_PENDING_PAYMENT = 'pending_payment';

    // Type filter
    const TYPE_FILTER_ANY = 'any';
    const TYPE_FILTER_TIME = 'time';
    const TYPE_FILTER_EXPENSES = 'expenses';

    // Job type filter
    const JOB_TYPE_FILTER_ANY = 'any';
    const JOB_TYPE_FILTER_SELECTED = 'selected';

    // Expenses categories filter
    const EXPENSE_CATEGORY_FILTER_ANY = 'any';
    const EXPENSE_CATEGORY_FILTER_SELECTED = 'selected';

    // Group
    const GROUP_BY_DATE = 'date';
    const GROUP_BY_PROJECT = 'project';
    const GROUP_BY_PROJECT_CLIENT = 'project_client';
    const GROUP_BY_USER = 'user';
    const GROUP_BY_JOB_TYPE = 'job_type';
    const GROUP_BY_EXPENSE_CATEGORY = 'expense_category';
    const GROUP_BY_BILLABLE_STATUS = 'billable_status';

    /**
     * Return company.
     *
     * If all records have one company use it, otherwise use the first that isn't owner
     *
     * @return Company
     */
    public function getCompany(User $user)
    {
        $this->ungroup();

        $client_ids = [];

        if ($results = $this->run($user)) {
            if ($results[0]['records'] && is_foreachable($results[0]['records'])) {
                foreach ($results[0]['records'] as $record) {
                    $client_ids[] = $record['client_id'];
                }
            }
        }

        $unique_client_ids = array_unique($client_ids);

        if (is_foreachable($unique_client_ids)) {
            if (count($unique_client_ids) == 1) {
                return Companies::findById($unique_client_ids[0]);
            } else {
                foreach ($unique_client_ids as $client_id) {
                    $company = DataObjectPool::get('Company', $client_id);

                    if ($company instanceof Company && !$company->getIsOwner()) {
                        return $company;
                    }
                }
            }
        }

        return AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();
    }

    public function run(User $user, array $additional = null): ?array
    {
        $conditions = $this->prepareConditions($user);

        if ($conditions !== false) {
            $queries = [];

            if ($this->queryTimeRecords()) {
                if ($this->getJobTypeFilter() == self::JOB_TYPE_FILTER_SELECTED) {
                    $queries[] = DB::prepare("(SELECT id, 'TimeRecord' AS 'type', parent_type, parent_id, job_type_id AS 'group_id', record_date, user_id, user_name, user_email, summary, value, billable_status FROM time_records WHERE $conditions AND job_type_id IN (?) ORDER BY record_date DESC)", $this->getJobTypeIds());
                } else {
                    $queries[] = "(SELECT id, 'TimeRecord' AS 'type', parent_type, parent_id, job_type_id AS 'group_id', record_date, user_id, user_name, user_email, summary, value, billable_status FROM time_records WHERE $conditions ORDER BY record_date DESC)";
                }
            }

            if ($this->queryExpenses()) {
                if ($this->getExpenseCategoryFilter() == self::EXPENSE_CATEGORY_FILTER_SELECTED) {
                    $queries[] = DB::prepare("(SELECT id, 'Expense' AS 'type', parent_type, parent_id, category_id AS 'group_id', record_date, user_id, user_name, user_email, summary, value, billable_status FROM expenses WHERE $conditions AND category_id IN (?) ORDER BY record_date DESC)", $this->getExpenseCategoryIds());
                } else {
                    $queries[] = "(SELECT id, 'Expense' AS 'type', parent_type, parent_id, category_id AS 'group_id', record_date, user_id, user_name, user_email, summary, value, billable_status FROM expenses WHERE $conditions ORDER BY record_date DESC)";
                }
            }

            if (count($queries) == 1) {
                $query = $queries[0];
            } else {
                $query = implode(' UNION ALL ', $queries) . ' ORDER BY record_date DESC';
            }

            if ($rows = DB::execute($query)) {
                $rows->setCasting(
                    [
                        'billable_status' => DBResult::CAST_INT,
                        'value' => DBResult::CAST_FLOAT,
                        'record_date' => DBResult::CAST_DATE,
                    ]
                );

                $rows = $rows->toArray();
                $this->populateProjectInfo($rows);
                $this->populateParentInfo($rows);

                $user_ids = [];
                foreach ($rows as $row) {
                    if ($row['user_id'] && !in_array($row['user_id'], $user_ids)) {
                        $user_ids[] = $row['user_id'];
                    }
                }

                $users = Users::getIdNameMap($user_ids);

                $job_types = $this->queryTimeRecords() ? JobTypes::getIdNameMap(true) : [];
                $expense_categories = $this->queryExpenses() ? ExpenseCategories::getIdNameMap(true) : [];

                // Populate date that's general for all grouping methods
                foreach ($rows as &$row) {
                    if ($row['user_id'] && $users && isset($users[$row['user_id']])) {
                        $row['user_name'] = $users[$row['user_id']];
                        $user_name = $users[$row['user_id']];
                    } else {
                        $row['user_name'] = $row['user_name'] ? $row['user_name'] : $row['user_email'];
                    }

                    if ($row['type'] == 'TimeRecord') {
                        $row['group_name'] = $row['group_id'] && isset($job_types[$row['group_id']]) ? $job_types[$row['group_id']] : '';
                    } else {
                        $row['group_name'] = $row['group_id'] && isset($expense_categories[$row['group_id']]) ? $expense_categories[$row['group_id']] : '';
                    }

                    // Billable filter
                    switch ($row['billable_status']) {
                        case ITrackingObject::BILLABLE:
                            $row['billable_name'] = 'Billable';

                            break;
                        case ITrackingObject::NOT_BILLABLE:
                            $row['billable_name'] = 'Not Billable';

                            break;
                        case ITrackingObject::PAID:
                            $row['billable_name'] = 'Paid';

                            break;
                        case ITrackingObject::PENDING_PAYMENT:
                            $row['billable_name'] = 'Pending Payment';

                            break;
                    }
                }

                unset($row); // break the reference

                $records = $this->groupRecordsForList($user, $rows);

                $this->calculateTotalsForList($records);

                return $records;
            }
        }

        return null;
    }

    /**
     * Prepare result conditions based on report settings.
     *
     * @param  User|IUser $user
     * @return string
     */
    public function prepareConditions(IUser $user)
    {
        $conditions = [DB::prepare('is_trashed = ?', false)];
        $only_tracking_enabled_projects = DB::prepare('is_tracking_enabled = ?', true);

        if ($project_ids = Projects::getProjectIdsByDataFilter($this, $user, $only_tracking_enabled_projects)) {
            $task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id IN (?) AND is_trashed = ?', $project_ids, false);

            if ($task_ids) {
                $conditions[] = DB::prepare('((parent_type = ? AND parent_id IN (?)) OR (parent_type = ? AND parent_id IN (?)))', 'Project', $project_ids, 'Task', $task_ids);
            } else {
                $conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', 'Project', $project_ids);
            }
        } else {
            return false; // No projects matching this report
        }

        $this->prepareUserFilterConditions($user, 'tracked', '', $conditions, 'user_id', STATE_DELETED);
        $this->prepareDateFilterConditions($user, 'tracked', '', $conditions, 'record_date');

        // Billable filter
        switch ($this->getBillableStatusFilter()) {
            case self::BILLABLE_FILTER_BILLABLE:
                $conditions[] = DB::prepare('(billable_status = ?)', ITrackingObject::BILLABLE);

                break;
            case self::BILLABLE_FILTER_NOT_BILLABLE:
                $conditions[] = DB::prepare('(billable_status = ? OR billable_status IS NULL)', ITrackingObject::NOT_BILLABLE);

                break;
            case self::BILLABLE_FILTER_BILLABLE_PAID:
                $conditions[] = DB::prepare('(billable_status >= ?)', ITrackingObject::PAID);

                break;
            case self::BILLABLE_FILTER_PENDING_PAYMENT:
                $conditions[] = DB::prepare('(billable_status = ?)', ITrackingObject::PENDING_PAYMENT);

                break;
            case self::BILLABLE_FILTER_BILLABLE_NOT_PAID:
                $conditions[] = DB::prepare('(billable_status IN (?))', [ITrackingObject::BILLABLE, ITrackingObject::PENDING_PAYMENT]);

                break;
            case self::BILLABLE_FILTER_BILLABLE_PENDING_OR_PAID:
                $conditions[] = DB::prepare('(billable_status >= ?)', ITrackingObject::BILLABLE);

                break;
        }

        // Make sure that we can filter by job types
        if ($this->getJobTypeFilter() == self::JOB_TYPE_FILTER_SELECTED) {
            $job_type_ids = $this->getJobTypeIds();

            if (empty($job_type_ids)) {
                throw new DataFilterConditionsError('job_type_filter', self::JOB_TYPE_FILTER_SELECTED, $job_type_ids, 'Select at leaset one job type');
            }
        }

        // Make sure that we can filter by expense categories
        if ($this->getExpenseCategoryFilter() == self::EXPENSE_CATEGORY_FILTER_SELECTED) {
            $expense_category_ids = $this->getExpenseCategoryIds();

            if (empty($expense_category_ids)) {
                throw new DataFilterConditionsError('expense_category_filter', self::EXPENSE_CATEGORY_FILTER_SELECTED, $expense_category_ids, 'Select at leaset one expense category');
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Return billable filter value.
     *
     * @return string
     */
    public function getBillableStatusFilter()
    {
        return $this->getAdditionalProperty('billable_status_filter', self::BILLABLE_FILTER_ALL);
    }

    // ---------------------------------------------------
    //  Export
    // ---------------------------------------------------

    /**
     * Return job type filter value.
     *
     * @return string
     */
    public function getJobTypeFilter()
    {
        return $this->getAdditionalProperty('job_type_filter', self::JOB_TYPE_FILTER_ANY);
    }

    /**
     * Return job type ID-s.
     *
     * @return array
     */
    public function getJobTypeIds()
    {
        return $this->getAdditionalProperty('job_type_ids', []);
    }

    /**
     * Return expense category filter value.
     *
     * @return string
     */
    public function getExpenseCategoryFilter()
    {
        return $this->getAdditionalProperty('expense_category_filter', self::EXPENSE_CATEGORY_FILTER_ANY);
    }

    /**
     * Return selected expense category ID-s.
     *
     * @return array
     */
    public function getExpenseCategoryIds()
    {
        return $this->getAdditionalProperty('expense_category_ids');
    }

    /**
     * Returns true if parent report queries time records table.
     *
     * @return bool
     */
    public function queryTimeRecords()
    {
        return $this->getTypeFilter() == self::TYPE_FILTER_ANY || $this->getTypeFilter() == self::TYPE_FILTER_TIME;
    }

    /**
     * Return type filter.
     *
     * @return string
     */
    public function getTypeFilter()
    {
        return $this->getAdditionalProperty('type_filter', self::TYPE_FILTER_ANY);
    }

    /**
     * Returns true if parent report queries expenses table.
     *
     * @return bool
     */
    public function queryExpenses()
    {
        return $this->getTypeFilter() == self::TYPE_FILTER_ANY || $this->getTypeFilter() == self::TYPE_FILTER_EXPENSES;
    }

    /**
     * Go through rows and load project_id and project_name information based on
     * parent type and parent_id information.
     *
     * @param array $rows
     */
    protected function populateProjectInfo(& $rows)
    {
        $tasks = [];
        $project_ids = [];

        foreach ($rows as &$row) {
            if ($row['parent_type'] == 'Task') {
                $tasks[(int) $row['parent_id']] = false;
            } elseif (!in_array((int) $row['parent_id'], $project_ids)) {
                $project_ids[] = (int) $row['parent_id'];
            }
        }

        unset($row); // break the reference

        // Load project ID-s for project objects
        if (count($tasks)) {
            if ($task_rows = DB::execute('SELECT id, name, project_id FROM tasks WHERE id IN (?)', array_keys($tasks))) {
                foreach ($task_rows as $task_row) {
                    $tasks[$task_row['id']] = ['name' => $task_row['name'], 'project_id' => $task_row['project_id']];

                    if (!in_array($task_row['project_id'], $project_ids)) {
                        $project_ids[] = $task_row['project_id'];
                    }
                }
            }
        }

        // Get project details
        $projects = [];

        $default_currency_id = Currencies::getDefaultId();

        $owner_company = AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();

        $default_company_id = $owner_company->getId();
        $default_company_name = $owner_company->getName();

        $project_rows = DB::execute(
            'SELECT id, name, company_id AS client_id, currency_id FROM projects WHERE id IN (?)',
            $project_ids
        );

        if ($project_rows) {
            $company_names = Companies::getIdNameMap();

            foreach ($project_rows as $project_row) {
                $projects[$project_row['id']] = [
                    'name' => $project_row['name'],
                    'currency_id' => $project_row['currency_id'] ? $project_row['currency_id'] : $default_currency_id,
                ];

                if ($project_row['client_id']) {
                    $projects[$project_row['id']]['client_id'] = $project_row['client_id'];
                    $projects[$project_row['id']]['client_name'] = isset($company_names[$project_row['client_id']]) ? $company_names[$project_row['client_id']] : lang('Unknown');
                } else {
                    $projects[$project_row['id']]['client_id'] = $default_company_id;
                    $projects[$project_row['id']]['client_name'] = $default_company_name;
                }
            }
        }

        $project_url = AngieApplication::getContainer()
            ->get(RouterInterface::class)
                ->assemble(
                    'project',
                    [
                        'project_id' => '--PROJECT-ID--',
                    ]
                );

        // Now, let's populate project ID, name and currency ID fields for records
        foreach ($rows as &$row) {
            $row['project_id'] = 0;
            $row['project_name'] = '--';
            $row['project_url'] = '#';
            $row['client_id'] = $default_company_id;
            $row['client_name'] = $default_company_name;
            $row['currency_id'] = $default_currency_id;

            if ($row['parent_type'] == 'Task') {
                $project_id = $tasks[$row['parent_id']] && isset($projects[$tasks[$row['parent_id']]['project_id']]) ? $tasks[$row['parent_id']]['project_id'] : 0;
            } else {
                $project_id = $row['parent_id'];
            }

            if ($project_id) {
                $row['project_id'] = $project_id;

                if ($row['type'] == 'TimeRecord') {
                    /** @var Project $project */
                    $project = DataObjectPool::get('Project', $project_id);

                    $custom_hourly_rates = JobTypes::getIdRateMapFor($project);

                    $row['custom_hourly_rate'] = $custom_hourly_rates[$row['group_id']];
                }

                if (isset($projects[$project_id])) {
                    $row['project_name'] = $projects[$project_id]['name'];
                    $row['project_url'] = str_replace('--PROJECT-ID--', $project_id, $project_url);
                    $row['currency_id'] = $projects[$project_id]['currency_id'];

                    $row['client_id'] = $projects[$project_id]['client_id'];
                    $row['client_name'] = $projects[$project_id]['client_name'];
                }
            }
        }

        unset($row); // Just in case...
    }

    private function populateParentInfo(array & $rows): void
    {
        $task_ids = [];

        foreach ($rows as &$row) {
            if ($row['parent_type'] == 'Task') {
                $task_ids[] = $row['parent_id'];
            }
        }
        unset($row);

        $tasks_info = [];

        if (count($task_ids)) {
            if ($info_rows = DB::execute('SELECT t.id, t.name, t.task_number, t.task_list_id, tl.name AS task_list_name FROM tasks AS t INNER JOIN task_lists AS tl ON t.task_list_id = tl.id WHERE t.id IN (?)', $task_ids)) {
                foreach ($info_rows as $info_row) {
                    $tasks_info[$info_row['id']] = ['name' => $info_row['name'], 'task_number' => $info_row['task_number'], 'task_list_id' => $info_row['task_list_id'], 'task_list_name' => $info_row['task_list_name']];
                }
            }
        }

        $task_url = AngieApplication::getContainer()
            ->get(RouterInterface::class)
                ->assemble(
                    'task',
                    [
                        'project_id' => '--PROJECT-ID--',
                        'task_id' => '--TASK_ID--',
                    ]
                );

        foreach ($rows as &$row) {
            if ($row['parent_type'] == 'Task') {
                $task_id = $row['parent_id'];
                $task_info = isset($tasks_info[$task_id]) ? $tasks_info[$task_id] : null;

                $name = isset($tasks_info[$task_id]) && $tasks_info[$task_id] ? $tasks_info[$task_id]['name'] : '';
                $url = $task_info ? str_replace(['--PROJECT-ID--', '--TASK_ID--'], [$row['project_id'], $task_id], $task_url) : '#';
                $row['task_number'] = isset($tasks_info[$task_id]) && $tasks_info[$task_id] ? (int) $tasks_info[$task_id]['task_number'] : '';
                $row['task_list_id'] = isset($tasks_info[$task_id]) && $tasks_info[$task_id] ? (int) $tasks_info[$task_id]['task_list_id'] : '';
                $row['task_list_name'] = isset($tasks_info[$task_id]) && $tasks_info[$task_id] ? $tasks_info[$task_id]['task_list_name'] : '';
            } elseif ($row['parent_type'] == 'Project') {
                $name = isset($row['project_name']) ? $row['project_name'] : '';
                $url = isset($row['project_url']) ? $row['project_url'] : '#';
            } else {
                $name = '';
                $url = '#';
            }

            $row['parent_name'] = $name;
            $row['parent_url'] = $url;
        }

        unset($row); // just in case
    }

    /**
     * Return grouped records for display in the list.
     *
     * @param  array $rows
     * @return array
     */
    private function groupRecordsForList(IUser $user, $rows)
    {
        $group_by = $this->getGroupBy();

        switch (array_shift($group_by)) {
            case self::GROUP_BY_DATE:
                return $this->groupByDateForList($user, $rows);

                break;
            case self::GROUP_BY_PROJECT:
                return $this->groupByProjectForList($rows);

                break;
            case self::GROUP_BY_PROJECT_CLIENT:
                return $this->groupByProjectClientForList($rows);

                break;
            case self::GROUP_BY_USER:
                return $this->groupByUserForList($rows);

                break;
            case self::GROUP_BY_JOB_TYPE:
                return $this->groupByGroup($rows, 'TimeRecord', 'job-type', 'expenses', lang('Expenses'));

                break;
            case self::GROUP_BY_EXPENSE_CATEGORY:
                return $this->groupByGroup($rows, 'Expense', 'expense-category', 'time-records', lang('Time Records'));

                break;
            case self::GROUP_BY_BILLABLE_STATUS:
                return $this->groupByBillableStatus($rows);

                break;
            default:
                return $this->groupUngroupedForList($rows);
        }
    }

    /**
     * Group records by date for list.
     *
     * @param  array $rows
     * @return array
     */
    private function groupByDateForList(IUser $user, $rows)
    {
        $result = [];

        foreach ($rows as $row) {
            if ($row['record_date'] instanceof DateValue) {
                $key = 'date-' . $row['record_date']->toMySQL();
                $record_date = $row['record_date']->formatForUser($user, 0);
            } else {
                $key = 'unknown';
                $record_date = lang('Unknown Date');
            }

            if (!isset($result[$key])) {
                $result[$key] = ['label' => $record_date, 'records' => []];
            }

            $result[$key]['records'][] = $row;
        }

        krsort($result);

        return $result;
    }

    /**
     * Group records by project.
     *
     * @param  array $rows
     * @return array
     */
    private function groupByProjectForList($rows)
    {
        $result = [];

        foreach ($rows as $row) {
            $group_key = 'project-' . $row['project_id'];

            if (empty($result[$group_key])) {
                $result[$group_key] = ['label' => trim($row['project_name']), 'records' => []];
            }

            $result[$group_key]['records'][] = $row;
        }

        $this->sortGroupedRecordsByLabel($result);

        return $result;
    }

    // ---------------------------------------------------
    //  Utilities
    // ---------------------------------------------------

    /**
     * Sort grouped records by group label.
     */
    private function sortGroupedRecordsByLabel(array & $result)
    {
        uasort($result, function ($a, $b) {
            return strcmp(strtolower($a['label']), strtolower($b['label']));
        });
    }

    /**
     * Group by project client for list display.
     *
     * @param  array $rows
     * @return array
     */
    private function groupByProjectClientForList($rows)
    {
        $result = [];

        $this->populateProjectClientInfo($rows);

        foreach ($rows as $row) {
            $group_key = 'client-' . $row['client_id'];

            if (!isset($result[$group_key])) {
                $result[$group_key] = ['label' => trim($row['client_name']), 'records' => []];
            }

            $result[$group_key]['records'][] = $row;
        }

        $this->sortGroupedRecordsByLabel($result);

        return $result;
    }

    /**
     * Add project client information to the rows.
     *
     * project_id field is required for rows for this function to work properly
     *
     * @param array $rows
     */
    protected function populateProjectClientInfo(& $rows)
    {
        $project_ids = [];

        foreach ($rows as &$row) {
            if ($row['project_id'] && !in_array($row['project_id'], $project_ids)) {
                $project_ids[] = $row['project_id'];
            }
        }
        unset($row);

        $projects = [];
        if (count($project_ids)) {
            if ($project_rows = DB::execute("SELECT projects.id AS 'id', projects.company_id AS 'client_id', companies.name AS 'client_name' FROM projects, companies WHERE projects.company_id = companies.id AND projects.id IN (?)", $project_ids)) {
                foreach ($project_rows as $project_row) {
                    $projects[$project_row['id']] = [
                        'client_id' => $project_row['client_id'],
                        'client_name' => $project_row['client_name'],
                    ];
                }
            }
        }

        $owner_company = AngieApplication::getContainer()
            ->get(OwnerCompanyResolverInterface::class)
                ->getCompany();

        foreach ($rows as &$row) {
            if (isset($projects[$row['project_id']])) {
                $row['client_id'] = $projects[$row['project_id']]['client_id'];
                $row['client_name'] = $projects[$row['project_id']]['client_name'];
            } else {
                $row['client_id'] = $owner_company->getId();
                $row['client_name'] = $owner_company->getName();
            }
        }
        unset($row); // just in case
    }

    /**
     * Group by record user for list display.
     *
     * @param  array $rows
     * @return array
     */
    private function groupByUserForList($rows)
    {
        $result = [];

        $user_ids = [];

        foreach ($rows as $row) {
            if ($row['user_id'] && !in_array($row['user_id'], $user_ids)) {
                $user_ids[] = $row['user_id'];
            }
        }

        $user_names = count($user_ids) ? Users::getIdNameMap($user_ids) : [];

        foreach ($rows as $row) {
            $group_key = 'user-' . $row['user_id'];

            if (!isset($result[$group_key])) {
                $result[$group_key] = ['label' => isset($user_names[$row['user_id']]) ? $user_names[$row['user_id']] : lang('Unknown'), 'records' => []];
            }

            $result[$group_key]['records'][] = $row;
        }

        $this->sortGroupedRecordsByLabel($result);

        return $result;
    }

    /**
     * Group by expense category for list display.
     *
     * @param  array  $rows
     * @param  string $record_type
     * @param  string $group_key_prefix
     * @param  string $other_records_key
     * @param  string $other_records_label
     * @return array
     */
    private function groupByGroup($rows, $record_type, $group_key_prefix, $other_records_key, $other_records_label)
    {
        $result = [];
        $other_records = [];

        foreach ($rows as $row) {
            if ($row['type'] == $record_type) {
                $group_key = "$group_key_prefix-$row[group_id]";

                if (empty($result[$group_key])) {
                    $result[$group_key] = ['label' => $row['group_name'], 'records' => []];
                }

                $result[$group_key]['records'][] = $row;
            } else {
                $other_records[] = $row;
            }
        }

        $this->sortGroupedRecordsByLabel($result);

        if (count($other_records)) {
            $result[$other_records_key] = ['label' => $other_records_label, 'records' => $other_records];
        }

        return $result;
    }

    /**
     * Group records by billable status.
     *
     * @param  array $rows
     * @return array
     */
    private function groupByBillableStatus($rows)
    {
        $result = [
            'billable-status-0' => ['label' => lang('Non-Billable'), 'records' => []],
            'billable-status-1' => ['label' => lang('Billable'), 'records' => []],
            'billable-status-2' => ['label' => lang('Pending Payment'), 'records' => []],
            'billable-status-3' => ['label' => lang('Paid'), 'records' => []],
        ];

        foreach ($rows as $row) {
            $result['billable-status-' . $row['billable_status']]['records'][] = $row;
        }

        foreach (['billable-status-0', 'billable-status-1', 'billable-status-2', 'billable-status-3'] as $status) {
            if (empty($result[$status]['records'])) {
                unset($result[$status]);
            }
        }

        return $result;
    }

    // ---------------------------------------------------
    //  Getters, setters and attributes
    // ---------------------------------------------------

    /**
     * Prepare ungrouped result for result.
     *
     * @param  array $rows
     * @return array
     */
    private function groupUngroupedForList($rows)
    {
        return ['all' => ['label' => lang('All Records'), 'records' => $rows]];
    }

    /**
     * Calculate totals for list.
     *
     * @param array $records
     */
    private function calculateTotalsForList(& $records)
    {
        $currencies = Currencies::findUsedCurrencies();

        foreach ($records as $group_key => $group) {
            $records[$group_key]['total_time'] = 0;
            $records[$group_key]['total_expenses'] = [];
            $records[$group_key]['has_expenses'] = false;

            foreach ($group['records'] as $record) {
                if ($record['type'] == 'TimeRecord') {
                    $records[$group_key]['total_time'] += time_to_minutes($record['value']);
                } else {
                    $currency_id = (int) $record['currency_id'];

                    if (empty($records[$group_key]['total_expenses'][$currency_id])) {
                        $records[$group_key]['total_expenses'][$currency_id] = ['value' => 0];
                    }

                    $records[$group_key]['total_expenses'][$currency_id]['value'] += $record['value'];
                }
            }

            $records[$group_key]['total_time'] = minutes_to_time($records[$group_key]['total_time']);
            foreach ($currencies as $currency) {
                $value = empty($records[$group_key]['total_expenses'][$currency->getId()]['value']) ? 0 : $records[$group_key]['total_expenses'][$currency->getId()]['value'];

                $records[$group_key]['total_expenses'][$currency->getId()]['currency_id'] = $currency->getId();

                if ($value) {
                    $records[$group_key]['has_expenses'] = true;
                    $records[$group_key]['total_expenses'][$currency->getId()]['verbose'] = $currency->format($value, null, true);
                } else {
                    $records[$group_key]['total_expenses'][$currency->getId()] = [
                        'value' => 0,
                        'verbose' => '0',
                    ];
                }
            }
        }
    }

    // ---------------------------------------------------
    //  Tracked by filter
    // ---------------------------------------------------

    /**
     * Return report total time.
     *
     * @param  string $status
     * @return float
     */
    public function getTotalTime(User $user, $status = null)
    {
        $this->setGroupBy(self::DONT_GROUP);

        $total = 0;

        if ($results = $this->run($user)) {
            if ($results[0]['records'] && is_foreachable($results[0]['records'])) {
                foreach ($results[0]['records'] as $record) {
                    if ($record['type'] == 'TimeRecord') {
                        if ($status) {
                            if ($record['billable_status'] == $status) {
                                $total += $record['value'];
                            }
                        } else {
                            $total += $record['value'];
                        }
                    }
                }
            }
        }

        return $total;
    }

    /**
     * Return report total expenses.
     *
     * @param  int          $status
     * @return float[]|null
     */
    public function getTotalExpenses(User $user, $status = null)
    {
        $this->setGroupBy(self::DONT_GROUP);

        $total = null;

        if ($results = $this->run($user)) {
            $total = [];

            if ($results[0]['records'] && is_foreachable($results[0]['records'])) {
                $currency_map = Currencies::getIdDetailsMap();

                foreach ($results[0]['records'] as $record) {
                    if ($record['type'] == 'Expense') {
                        if ($status) {
                            if ($record['billable_status'] == $status) {
                                $total[$currency_map[$record['currency_id']]['code']] += $record['value'];
                            }
                        } else {
                            $total[$currency_map[$record['currency_id']]['code']] += $record['value'];
                        }
                    }
                }
            }
        }

        return $total;
    }

    public function getExportColumns(): array
    {
        return [
            'Type',
            'Record ID',
            'Group ID',
            'Group Name',
            'Parent Type',
            'Parent ID',
            'Parent Name',
            'Project ID',
            'Project Name',
            'Client ID',
            'Client Name',
            'Record Date',
            'User ID',
            'User Name',
            'User Email',
            'Summary',
            'Value',
            'Status',
            'Task List Id',
            'Task List Name',
        ];
    }

    public function exportWriteLines(User $user, array $result): void
    {
        foreach ($result as $group_name => $group_data) {
            if (isset($group_data['records']) && is_array($group_data['records'])) {
                foreach ($group_data['records'] as $record) {
                    switch ($record['billable_status']) {
                        case ITrackingObject::NOT_BILLABLE:
                            $status = 'Not Billable';

                            break;
                        case ITrackingObject::BILLABLE:
                            $status = 'Billable';

                            break;
                        case ITrackingObject::PENDING_PAYMENT:
                            $status = 'Pending Payment';

                            break;
                        case ITrackingObject::PAID:
                            $status = 'Paid';

                            break;
                        default:
                            $status = 'Unknown';
                    }

                    if ($record['parent_type'] === 'Task') {
                        $task_list_info = DB::executeFirstRow('SELECT tl.id AS id, tl.name AS task_list_name FROM tasks AS t JOIN task_lists AS tl ON t.task_list_id = tl.id WHERE t.id = ?', $record['parent_id']);
                        $task_list_id = $task_list_info['id'];
                        $task_list_name = $task_list_info['task_list_name'];
                    } else {
                        $task_list_id = '';
                        $task_list_name = 'Uncategorized';
                    }

                    $this->exportWriteLine(
                        [
                            $record['type'],
                            $record['id'],
                            $record['group_id'],
                            $record['group_name'],
                            $record['parent_type'],
                            $record['parent_id'],
                            $record['parent_name'],
                            $record['project_id'],
                            $record['project_name'],
                            $record['client_id'],
                            $record['client_name'],
                            $record['record_date'] instanceof DateValue ? $record['record_date']->toMySQL() : null,
                            $record['user_id'],
                            $record['user_name'],
                            $record['user_email'],
                            $record['summary'],
                            $record['value'],
                            $status,
                            $task_list_id,
                            $task_list_name,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        switch ($attribute) {
            case 'tracked_by_filter':
                $this->setUserFilterAttribute('tracked', $value);

                break;
            case 'project_filter':
                if (str_starts_with((string) $value, Projects::PROJECT_FILTER_CATEGORY)) {
                    $this->filterByProjectCategory($this->getIdFromFilterValue($value));
                } elseif (str_starts_with((string) $value, Projects::PROJECT_FILTER_CLIENT)) {
                    $this->filterByProjectClient($this->getIdFromFilterValue($value));
                } elseif (str_starts_with((string) $value, Projects::PROJECT_FILTER_SELECTED)) {
                    $this->filterByProjects($this->getIdsFromFilterValue($value));
                } else {
                    $this->setProjectFilter($value);
                }

                break;
            case 'tracked_on_filter':
                $this->setDateFilterAttribute('tracked', $value);

                break;
            case 'job_type_filter':
                if (str_starts_with((string) $value, self::JOB_TYPE_FILTER_SELECTED)) {
                    $this->filterByJobTypes($this->getIdsFromFilterValue($value));
                } else {
                    $this->setJobTypeFilter($value);
                }

                break;
            case 'expense_category_filter':
                if (str_starts_with((string) $value, self::EXPENSE_CATEGORY_FILTER_SELECTED)) {
                    $this->filterByExpenseCategory($this->getIdsFromFilterValue($value));
                } else {
                    $this->setExpenseCategoryFilter($value);
                }

                break;
            case 'type_filter':
                $this->setTypeFilter($value);

                break;
            case 'billable_status_filter':
                $this->setBillableStatusFilter($value);

                break;
            case 'group_by':
                parent::setAttribute($attribute, $value);
        }
    }

    /**
     * Set filter to filter records by project category.
     *
     * @param  int $project_category_id
     * @return int
     */
    public function filterByProjectCategory($project_category_id)
    {
        $this->setProjectFilter(Projects::PROJECT_FILTER_CATEGORY);
        $this->setAdditionalProperty('project_category_id', (int) $project_category_id);
    }

    // ---------------------------------------------------
    //  Tracked on filter
    // ---------------------------------------------------

    /**
     * Set project filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setProjectFilter($value)
    {
        return $this->setAdditionalProperty('project_filter', $value);
    }

    /**
     * Set filter to filter records by project client.
     *
     * @param  int $project_client_id
     * @return int
     */
    public function filterByProjectClient($project_client_id)
    {
        $this->setProjectFilter(Projects::PROJECT_FILTER_CLIENT);
        $this->setAdditionalProperty('project_client_id', (int) $project_client_id);
    }

    /**
     * Set this report to filter records by project ID-s.
     *
     * @param  array $project_ids
     * @return array
     */
    public function filterByProjects($project_ids)
    {
        $this->setProjectFilter(Projects::PROJECT_FILTER_SELECTED);

        if (is_array($project_ids)) {
            foreach ($project_ids as $k => $v) {
                $project_ids[$k] = (int) $v;
            }
        } else {
            $project_ids = null;
        }

        $this->setAdditionalProperty('project_ids', $project_ids);
    }

    /**
     * Set job type filter to list of given values.
     *
     * @param array $job_type_ids
     */
    public function filterByJobTypes($job_type_ids)
    {
        if (is_array($job_type_ids)) {
            $this->setJobTypeFilter(self::JOB_TYPE_FILTER_SELECTED);

            foreach ($job_type_ids as $k => $v) {
                $job_type_ids[$k] = (int) $v;
            }

            $this->setAdditionalProperty('job_type_ids', $job_type_ids);
        } else {
            throw new InvalidParamError('job_type_ids', $job_type_ids, 'List of job type IDs should be an array');
        }
    }

    /**
     * Set job type filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setJobTypeFilter($value)
    {
        return $this->setAdditionalProperty('job_type_filter', $value);
    }

    /**
     * Set expense category filter to list of selected ID-s.
     *
     * @param array $expense_category_ids
     */
    public function filterByExpenseCategory($expense_category_ids)
    {
        if (is_array($expense_category_ids)) {
            $this->setExpenseCategoryFilter(self::EXPENSE_CATEGORY_FILTER_SELECTED);

            foreach ($expense_category_ids as $k => $v) {
                $expense_category_ids[$k] = (int) $v;
            }

            $this->setAdditionalProperty('expense_category_ids', $expense_category_ids);
        } else {
            throw new InvalidParamError('expense_category_ids', $expense_category_ids, 'List of expense category IDs should be an array');
        }
    }

    /**
     * Set expense category filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setExpenseCategoryFilter($value)
    {
        return $this->setAdditionalProperty('expense_category_filter', $value);
    }

    /**
     * Set type filter.
     *
     * @param  string $value
     * @return string
     */
    public function setTypeFilter($value)
    {
        return $this->setAdditionalProperty('type_filter', $value);
    }

    /**
     * Set billable filter to a given value.
     *
     * @param  string $value
     * @return string
     */
    public function setBillableStatusFilter($value)
    {
        return $this->setAdditionalProperty('billable_status_filter', $value);
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $this->describeUserFilter('tracked', $result);
        $this->describeDateFilter('tracked', $result);

        $result['type_filter'] = $this->getTypeFilter();
        $result['job_type_filter'] = $this->getJobTypeFilter();
        $result['job_type_ids'] = $this->getJobTypeIds();
        $result['expense_category_filter'] = $this->getExpenseCategoryFilter();
        $result['expense_category_ids'] = $this->getExpenseCategoryIds();
        $result['billable_status_filter'] = $this->getBillableStatusFilter();
        $result['group_by'] = $this->getGroupBy()[0];

        // Project filter
        $result['project_filter'] = $this->getProjectFilter();
        switch ($result['project_filter']) {
            case Projects::PROJECT_FILTER_CATEGORY:
                $result['project_category_id'] = $this->getProjectCategoryId();

                break;
            case Projects::PROJECT_FILTER_CLIENT:
                $result['project_client_id'] = $this->getProjectClientId();

                break;
            case Projects::PROJECT_FILTER_SELECTED:
                $result['project_ids'] = $this->getProjectIds();

                break;
        }

        return $result;
    }

    /**
     * Return tracked by filter.
     *
     * @return string
     */
    public function getTrackedByFilter()
    {
        return $this->getAdditionalProperty('tracked_by_filter', self::USER_FILTER_ANYBODY);
    }

    /**
     * Set tracked by fileter to the list of users.
     *
     * @param array $user_ids
     */
    public function trackedByUsers($user_ids)
    {
        $this->setTrackedByFilter(self::USER_FILTER_SELECTED);

        if (is_array($user_ids)) {
            foreach ($user_ids as $k => $v) {
                $user_ids[$k] = (int) $v;
            }
        } else {
            if ($user_ids) {
                $user_ids = [$user_ids];
            } else {
                $user_ids = null;
            }
        }

        $this->setAdditionalProperty('tracked_by_users', $user_ids);
    }

    // ---------------------------------------------------
    //  Project Filter
    // ---------------------------------------------------

    /**
     * Set tracked by filter.
     *
     * @param  string $value
     * @return string
     */
    public function setTrackedByFilter($value)
    {
        return $this->setAdditionalProperty('tracked_by_filter', $value);
    }

    /**
     * Return array of selected users.
     *
     * @return array
     */
    public function getTrackedByUsers()
    {
        return $this->getAdditionalProperty('tracked_by_users');
    }

    /**
     * Return a list of company ID-s.
     *
     * @return array
     */
    public function getTrackedByCompanyMember()
    {
        return $this->getAdditionalProperty('tracked_by_company_ids');
    }

    /**
     * Return only records that are recroded by members of these companies.
     *
     * @param array|int $company_ids
     */
    public function trackedByCompanyMember($company_ids)
    {
        if (empty($company_ids)) {
            throw new InvalidParamError('company_id', $company_ids, 'One or more company ID-s is required');
        }

        $company_ids = (array) $company_ids;

        $this->setTrackedByFilter(self::USER_FILTER_COMPANY_MEMBER);
        $this->setAdditionalProperty('tracked_by_company_ids', $company_ids);
    }

    /**
     * Return tracked on filter value.
     *
     * @return string
     */
    public function getTrackedOnFilter()
    {
        return $this->getAdditionalProperty('tracked_on_filter', self::DATE_FILTER_ANY);
    }

    /**
     * Return tracked in year value.
     *
     * @return int
     */
    public function getTrackedInYear()
    {
        return $this->getAdditionalProperty('tracked_on_filter_year');
    }

    /**
     * Set tracked in year filter.
     *
     * @param int $year
     */
    public function setTrackedInYear($year)
    {
        $this->setTrackedOnFilter(self::DATE_FILTER_SELECTED_YEAR);
        $this->setAdditionalProperty('tracked_on_filter_year', (int) $year);
    }

    /**
     * Set tracked on filter to a given $value.
     *
     * @param  string $value
     * @return string
     */
    public function setTrackedOnFilter($value)
    {
        return $this->setAdditionalProperty('tracked_on_filter', $value);
    }

    // ---------------------------------------------------
    //  Billable status filter
    // ---------------------------------------------------

    /**
     * Return tracked age.
     *
     * @return int
     */
    public function getTrackedAge()
    {
        return (int) $this->getAdditionalProperty('tracked_age');
    }

    /**
     * Set tracked age.
     *
     * @param  int    $value
     * @param  string $filter
     * @return int
     */
    public function trackedAge($value, $filter = DataFilter::DATE_FILTER_AGE_IS)
    {
        if ($filter == DataFilter::DATE_FILTER_AGE_IS || DataFilter::DATE_FILTER_AGE_IS_LESS_THAN || $filter == DataFilter::DATE_FILTER_AGE_IS_MORE_THAN) {
            $this->setTrackedOnFilter($filter);
        } else {
            throw new InvalidParamError('filter', $filter);
        }

        return $this->setAdditionalProperty('tracked_age', (int) $value);
    }

    // ---------------------------------------------------
    //  Type filter status
    // ---------------------------------------------------

    /**
     * Set tracked on date.
     *
     * @return DateValue|null
     */
    public function getTrackedOnDate()
    {
        $on = $this->getAdditionalProperty('tracked_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Set tracked on date filter.
     *
     * @param string $date
     */
    public function trackedOnDate($date)
    {
        $this->setTrackedOnFilter(self::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('tracked_on_filter_on', (string) $date);
    }

    // ---------------------------------------------------
    //  Job type filter
    // ---------------------------------------------------

    /**
     * Set tracked before date filter (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function trackedBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setTrackedOnFilter($filter);
        $this->setAdditionalProperty('tracked_on_filter_on', (string) $date);
    }

    /**
     * Set tracked after date filter (including that date if $inclusive set to TRUE).
     *
     * @param string $date
     */
    public function trackedAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setTrackedOnFilter($filter);
        $this->setAdditionalProperty('tracked_on_filter_on', (string) $date);
    }

    /**
     * Get tracked in range filter.
     *
     * @return DateValue[]
     */
    public function getTrackedInRange()
    {
        $from = $this->getAdditionalProperty('tracked_on_filter_from');
        $to = $this->getAdditionalProperty('tracked_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    /**
     * Filter records tracked in a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function trackedInRange($from, $to)
    {
        $this->setTrackedOnFilter(self::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('tracked_on_filter_from', (string) $from);
        $this->setAdditionalProperty('tracked_on_filter_to', (string) $to);
    }

    // ---------------------------------------------------
    //  Expense categories filter
    // ---------------------------------------------------

    /**
     * Return project filter value.
     *
     * @return string
     */
    public function getProjectFilter()
    {
        return $this->getAdditionalProperty('project_filter', Projects::PROJECT_FILTER_ANY);
    }

    /**
     * Return project category ID.
     *
     * @return int
     */
    public function getProjectCategoryId()
    {
        return (int) $this->getAdditionalProperty('project_category_id');
    }

    /**
     * Return project client ID.
     *
     * @return int
     */
    public function getProjectClientId()
    {
        return (int) $this->getAdditionalProperty('project_client_id');
    }

    /**
     * Return project ID-s.
     *
     * @return array
     */
    public function getProjectIds()
    {
        return $this->getAdditionalProperty('project_ids');
    }

    /**
     * @return bool
     */
    public function getSumByUser()
    {
        return false;
    }

    /**
     * Use by managers for serious reporting, so it needs to go through all projects.
     *
     * @return bool
     */
    public function getIncludeAllProjects()
    {
        return true;
    }

    public function canRun(User $user): bool
    {
        return $user->isPowerUser() || $user->isFinancialManager();
    }

    public function canEdit(User $user): bool
    {
        return $user->isPowerUser();
    }

    public function canDelete(User $user): bool
    {
        return $user->isPowerUser();
    }

    // ---------------------------------------------------
    //  Invoice based on
    // ---------------------------------------------------

    /**
     * Prepare report so invoice can be created from it.
     *
     * @return TrackingFilter
     */
    public function prepareReportForInvoiceBasedOn()
    {
        $this->setGroupBy(self::DONT_GROUP);

        return $this;
    }
}
