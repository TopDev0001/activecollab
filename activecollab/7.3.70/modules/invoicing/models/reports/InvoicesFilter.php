<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\RelatedInvoiceProjectsInterface;
use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\Resolver\RelatedInvoiceProjectsResolverInterface;
use Angie\Globalization;

class InvoicesFilter extends DataFilter
{
    const STATUS_FILTER_ANY = 'any';
    const STATUS_FILTER_ISSUED = 'issued';
    const STATUS_FILTER_OVERDUE = 'overdue';
    const STATUS_FILTER_UNSENT = 'unsent';
    const STATUS_FILTER_SENT = 'sent';
    const STATUS_FILTER_PAID = 'paid';
    const STATUS_FILTER_CANCELED = 'canceled';
    const STATUS_FILTER_ALL_EXCEPT_CANCELED = 'all_except_canceled';
    const STATUS_FILTER_PARTIALLY_PAID = 'partially_paid';
    const STATUS_FILTER_OVERDUE_PARTIALLY_PAID = 'overdue_partially_paid';

    const CLIENT_FILTER_ANY = 'any';
    const CLIENT_FILTER_SELECTED = 'selected';
    const CLIENT_FILTER_CHOOSEN = 'choosen';

    public function run(User $user, array $additional = null): ?array
    {
        [
            $invoices,
            $companies,
        ] = $this->queryInvoicesData($user, $additional);

        if (is_array($invoices)) {
            $result = [];

            $group_by = $this->getGroupBy();

            $this->groupInvoices(
                array_shift($group_by),
                $user,
                $invoices,
                $companies,
                $result
            );

            foreach (array_keys($result) as $k) {
                if ($result[$k]['invoices']) {
                    foreach (array_keys($result[$k]['invoices']) as $invoice_id) {
                        $status = $this->getStatusFilter();

                        if (
                            $result[$k]['invoices'][$invoice_id]['status'] !== $status
                            && !in_array(
                                $status,
                                [
                                    self::STATUS_FILTER_ANY,
                                    self::STATUS_FILTER_ALL_EXCEPT_CANCELED,
                                ]
                            )
                        ) {
                            unset($result[$k]['invoices'][$invoice_id]);
                        }
                    }
                }
            }

            if (count($group_by) > 0) {
                $this->groupInvoicesSecondWave(
                    array_shift($group_by),
                    $user,
                    $result,
                    $companies
                );

                foreach ($result as $first_level => $first_level_invoices) {
                    foreach (array_keys($first_level_invoices['invoices']) as $second_level) {
                        $this->prepareGroupTotals($result[$first_level]['invoices'][$second_level]);
                    }
                }
            } else {
                foreach (array_keys($result) as $k) {
                    $this->prepareGroupTotals($result[$k]);
                }
            }

            return $result;
        }

        return null;
    }

    public function getExportColumns(): array
    {
        return [
            'ID',
            'Number',
            'Status',
            'Currency Name',
            'Currency Code',
            'Company ID',
            'Company',
            'Issued On',
            'Due On',
            'Closed On',
            'Paid Amount',
            'Balance Due',
            'Total',
            'Project ID',
            'Project Name',
        ];
    }

    public function exportWriteLines(User $user, array $result): void
    {
        $currencies = Currencies::getIdDetailsMap();

        foreach ($result as $v) {
            if ($v['invoices']) {
                foreach ($v['invoices'] as $invoice) {
                    $currency_id = $invoice['currency_id'];

                    $this->exportWriteLine(
                        [
                            $invoice['id'],
                            $invoice['name'],
                            $invoice['status'],
                            isset($currencies[$currency_id]) ? $currencies[$currency_id]['name'] : null,
                            isset($currencies[$currency_id]) ? $currencies[$currency_id]['code'] : null,
                            $invoice['company_id'] ? $invoice['company_id'] : 0,
                            $invoice['company_name'] ? $invoice['company_name'] : '',
                            $invoice['issued_on'] instanceof DateValue ? $invoice['issued_on']->toMySQL() : null,
                            $invoice['due_on'] instanceof DateValue ? $invoice['due_on']->toMySQL() : null,
                            $invoice['closed_on'] instanceof DateValue ? $invoice['closed_on']->toMySQL() : null,
                            (float) $invoice['paid_amount'],
                            (float) $invoice['balance_due'],
                            (float) $invoice['total'],
                            $invoice['project_id'] ? $invoice['project_id'] : 0,
                            $invoice['project_name'] ? $invoice['project_name'] : '',
                        ]
                    );
                }
            }
        }
    }

    protected function prepareRecordDetails(
        array & $invoice,
        RelatedInvoiceProjectsInterface $related_invoice_projects,
        ?array $companies
    ): void
    {
        $company_id = array_var($invoice, 'company_id', 0);
        $invoice['name'] = Invoices::getInvoiceName($invoice['number'], true);

        if ($company_id && !empty($companies[$company_id])) {
            $invoice['company_name'] = $companies[$company_id];
        }

        $invoice['project_name'] = $related_invoice_projects->getRelatedProjectNames($invoice['id']);
        $invoice['related_projects'] = $related_invoice_projects->getRelatedProjects($invoice['id']);

        if (empty($invoice['project_id']) && count($invoice['related_projects']) === 1) {
            $invoice['project_id'] = array_keys($invoice['related_projects'][0])[0];
        }
    }

    public function prepareGroupTotals(array & $group): void
    {
        $group['total_due'] = [];
        $group['total'] = [];

        if ($group['invoices']) {
            foreach ($group['invoices'] as $invoice) {
                $currency_id = $invoice['currency_id'];

                if (!isset($group['total_due'][$currency_id])) {
                    $group['total_due'][$currency_id] = 0;
                }

                if (!isset($group['total'][$currency_id])) {
                    $group['total'][$currency_id] = 0;
                }

                $group['total_due'][$currency_id] += $invoice['balance_due'];
                $group['total'][$currency_id] += $invoice['total'];
            }
        }
    }

    // ---------------------------------------------------
    //  Query and Group the Data
    // ---------------------------------------------------

    /**
     * Group invoices.
     *
     * @param string         $group_by
     * @param DBResult|array $invoices
     * @param array          $companies
     */
    protected function groupInvoices($group_by, User $user, $invoices, $companies, array & $result)
    {
        switch ($group_by) {
            case self::GROUP_BY_STATUS:
                $this->groupByStatus($user, $invoices, $result);
                break;
            case self::GROUP_BY_PROJECT:
                $this->groupByProject($invoices, $result);
                break;
            case self::GROUP_BY_CLIENT:
                $this->groupByClient($invoices, $companies, $result);
                break;
            case self::GROUP_BY_ISSUED_ON:
                $this->groupByDateField($invoices, $user, 'issued_on', $result);
                break;
            case self::GROUP_BY_DUE_ON:
                $this->groupByDateField($invoices, $user, 'due_on', $result);
                break;
            case self::GROUP_BY_CLOSED_ON:
                $this->groupByDateField($invoices, $user, 'closed_on', $result);
                break;
            default:
                $this->groupUngrouped($invoices, $result);
        }
    }

    public function groupInvoicesSecondWave(
        string $group_by,
        User $user,
        array & $invoices,
        ?array $companies
    ): void
    {
        foreach (array_keys($invoices) as $k) {
            $result = [];

            switch ($group_by) {
                case self::GROUP_BY_STATUS:
                    $this->groupByStatus($user, $invoices[$k]['invoices'], $result);
                    break;
                case self::GROUP_BY_PROJECT:
                    $this->groupByProject($invoices[$k]['invoices'], $result);
                    break;
                case self::GROUP_BY_CLIENT:
                    $this->groupByClient($invoices[$k]['invoices'], $companies, $result);
                    break;
                case self::GROUP_BY_ISSUED_ON:
                    $this->groupByDateField($invoices[$k]['invoices'], $user, 'issued_on', $result);
                    break;
                case self::GROUP_BY_DUE_ON:
                    $this->groupByDateField($invoices[$k]['invoices'], $user, 'due_on', $result);
                    break;
                case self::GROUP_BY_CLOSED_ON:
                    $this->groupByDateField($invoices[$k]['invoices'], $user, 'closed_on', $result);
                    break;
                default:
                    return;
            }

            $invoices[$k]['invoices'] = $result;
        }
    }

    private function getStatusLangValue(string $status): ?string
    {
        switch ($status) {
            case self::STATUS_FILTER_SENT:
                return lang('Sent');
            case self::STATUS_FILTER_PAID:
                return lang('Paid');
            case self::STATUS_FILTER_CANCELED:
                return lang('Canceled');
            case self::STATUS_FILTER_OVERDUE:
                return lang('Overdue');
            case self::STATUS_FILTER_UNSENT:
                return lang('Unsent');
            case self::STATUS_FILTER_PARTIALLY_PAID:
                return lang('Partially Paid');
            case self::STATUS_FILTER_OVERDUE_PARTIALLY_PAID:
                return lang('Overdue/Partially Paid');
        }

        return null;
    }

    private function groupByStatus(User $user, array $invoices, array & $result): void
    {
        $result = [
            Invoice::PAID => ['label' => lang('Paid'), 'invoices' => []],
            Invoice::CANCELED => ['label' => lang('Canceled'), 'invoices' => []],
            Invoice::OVERDUE => ['label' => lang('Overdue'), 'invoices' => []],
            Invoice::SENT => ['label' => lang('Sent'), 'invoices' => []],
            Invoice::UNSENT => ['label' => lang('Unsent'), 'invoices' => []],
            self::STATUS_FILTER_PARTIALLY_PAID => ['label' => lang('Partially Paid'), 'invoices' => []],
            self::STATUS_FILTER_OVERDUE_PARTIALLY_PAID => ['label' => lang('Overdue And Partially Paid'), 'invoices' => []],
        ];

        foreach ($invoices as $invoice) {
            switch ($invoice['status']) {
                case Invoice::PAID:
                    $result[Invoice::PAID]['invoices'][$invoice['id']] = $invoice;
                    break;
                case Invoice::CANCELED:
                    $result[Invoice::CANCELED]['invoices'][$invoice['id']] = $invoice;
                    break;
                case Invoice::OVERDUE:
                    $result[Invoice::OVERDUE]['invoices'][$invoice['id']] = $invoice;
                    break;
                case Invoice::SENT:
                    $result[Invoice::SENT]['invoices'][$invoice['id']] = $invoice;
                    break;
                case Invoice::UNSENT:
                    $result[Invoice::UNSENT]['invoices'][$invoice['id']] = $invoice;
                    break;
                case self::STATUS_FILTER_PARTIALLY_PAID:
                    $result[self::STATUS_FILTER_PARTIALLY_PAID]['invoices'][$invoice['id']] = $invoice;
                    break;
                case self::STATUS_FILTER_OVERDUE_PARTIALLY_PAID:
                    $result[self::STATUS_FILTER_OVERDUE_PARTIALLY_PAID]['invoices'][$invoice['id']] = $invoice;
                    break;
            }
        }

        foreach (['paid', 'canceled', 'overdue', 'sent', 'unsent', 'partially_paid', 'overdue_partially_paid'] as $status) {
            if (empty($result[$status]['invoices'])) {
                unset($result[$status]);
            }
        }
    }

    private function groupByProject(array $invoices, array & $result): void
    {
        $multiple_projects = [];
        $unknown_projects = [];

        foreach ($invoices as $invoice) {
            switch (count($invoice['related_projects'])) {
                case 0:
                    $unknown_projects[$invoice['id']] = $invoice;
                    break;
                case 1:
                    $project_name = $invoice['project_name'];

                    if (empty($project_name)) {
                        $unknown_projects[$invoice['id']] = $invoice;
                        continue 2;
                    }

                    $project_group_key = sprintf('project-%d', $invoice['project_id']);

                    if (empty($result[$project_group_key])) {
                        $result[$project_group_key] = [
                            'label' => $project_name,
                            'invoices' => [],
                        ];
                    }

                    $result[$project_group_key]['invoices'][$invoice['id']] = $invoice;

                    break;
                default:
                    $multiple_projects[$invoice['id']] = $invoice;
            }
        }

        if (!empty($multiple_projects)) {
            $result['multiple-projects'] = [
                'label' => lang('Multiple Projects'),
                'invoices' => $multiple_projects,
            ];
        }

        if (!empty($unknown_projects)) {
            $result['unknow-project'] = [
                'label' => lang('Unknown Project'),
                'invoices' => $unknown_projects,
            ];
        }
    }

    /**
     * Return invoices grouped by client company.
     *
     * @param array $invoices
     * @param array $companies
     */
    private function groupByClient($invoices, $companies, array & $result)
    {
        if ($companies) {
            foreach ($companies as $k => $v) {
                $result["company-$k"] = ['label' => $v, 'invoices' => []];
            }
        }

        foreach ($invoices as $invoice) {
            $company_id = $invoice['company_id'];

            if (isset($result["company-$company_id"])) {
                $result["company-$company_id"]['invoices'][$invoice['id']] = $invoice;
            } else {
                if (!isset($result['company-' . $invoice['company_name']])) {
                    $result['company-' . $invoice['company_name']]['label'] = $invoice['company_name'];
                }
                $result['company-' . $invoice['company_name']]['invoices'][$invoice['id']] = $invoice;
            }
        }
    }

    /**
     * Return invoices grouped by client company.
     *
     * @param array  $invoices
     * @param User   $user
     * @param string $date_field
     */
    private function groupByDateField($invoices, $user, $date_field, array & $result)
    {
        if ($date_field == 'due_on' || $date_field == 'issued_on') {
            $not_set_label = lang('Draft Invoices');
        } else {
            $not_set_label = lang('Not Paid Yet, or Canceled');
        }

        $date_not_set = [
            'label' => $not_set_label,
            'invoices' => [],
        ];

        foreach ($invoices as $invoice) {
            $date = $invoice[$date_field];

            if ($date instanceof DateValue) {
                $key = 'date-' . $date->toMySQL();

                if (!isset($result[$key])) {
                    $result[$key] = ['label' => $date->formatDateForUser($user, 0), 'invoices' => []];
                }

                $result[$key]['invoices'][$invoice['id']] = $invoice;
            } else {
                $date_not_set['invoices'][$invoice['id']] = $invoice;
            }
        }
    }

    /**
     * Return invoices grouped in All group (ungrouped).
     *
     * @param array $invoices
     */
    private function groupUngrouped($invoices, array & $result)
    {
        $result['all'] = [
            'label' => lang('All Invoices'),
            'invoices' => [],
        ];

        foreach ($invoices as $invoice) {
            $result['all']['invoices'][$invoice['id']] = $invoice;
        }
    }

    protected function queryInvoicesData(User $user, array $additional = null): array
    {
        try {
            $conditions = $this->prepareConditions($user, $additional);
        } catch (DataFilterConditionsError $e) {
            $conditions = null;
        }

        $invoice_ids = [];
        $companies = [];

        if ($conditions) {
            $raw_invoices = DB::execute("SELECT * FROM `invoices` WHERE {$conditions} ORDER BY `issued_on` DESC");

            if (!empty($raw_invoices)) {
                $raw_invoices->setCasting(
                    [
                        'subtotal' => DBResult::CAST_FLOAT,
                        'tax' => DBResult::CAST_FLOAT,
                        'total' => DBResult::CAST_FLOAT,
                        'balance_due' => DBResult::CAST_FLOAT,
                        'paid_amount' => DBResult::CAST_FLOAT,
                        'created_on' => DBResult::CAST_DATETIME,
                        'issued_on' => DBResult::CAST_DATETIME,
                        'due_on' => DBResult::CAST_DATE,
                        'closed_on' => DBResult::CAST_DATETIME,
                    ]
                );

                $invoices = [];

                foreach ($raw_invoices as $invoice) {
                    $company_id = $invoice['company_id'];

                    if ($company_id && !isset($companies[$company_id])) {
                        $companies[$company_id] = null;
                    }

                    if ($invoice['issued_on'] !== null && $invoice['closed_on'] === null) {
                        // overdue status
                        if (intval($invoice['paid_amount']) === 0 && $invoice['due_on']->getTimestamp() < DateTimeValue::now()->getTimestamp()) {
                            $invoice['status'] = self::STATUS_FILTER_OVERDUE;
                            $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_OVERDUE);

                        // sent status
                        } elseif ($invoice['sent_on'] !== null) {
                            $invoice['status'] = self::STATUS_FILTER_SENT;
                            $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_SENT);

                        // partially paid status
                        } elseif ($invoice['balance_due'] > 0 && $invoice['paid_amount'] > 0 && $invoice['due_on']->getTimestamp() >= DateTimeValue::now()->getTimestamp()) {
                            $invoice['status'] = self::STATUS_FILTER_PARTIALLY_PAID;
                            $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_PARTIALLY_PAID);

                        // overdue and partially paid status
                        } elseif ($invoice['balance_due'] > 0 && $invoice['paid_amount'] > 0 && $invoice['due_on']->getTimestamp() < DateTimeValue::now()->getTimestamp()) {
                            $invoice['status'] = self::STATUS_FILTER_OVERDUE_PARTIALLY_PAID;
                            $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_OVERDUE_PARTIALLY_PAID);

                        // unsent status
                        } elseif (intval($invoice['paid_amount']) === 0 && empty($invoice['recipients']) && $invoice['due_on']->getTimestamp() >= DateTimeValue::now()->getTimestamp()) {
                            $invoice['status'] = self::STATUS_FILTER_UNSENT;
                            $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_UNSENT);

                        // issued status
                        } else {
                            $invoice['status'] = self::STATUS_FILTER_ISSUED;
                            $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_ISSUED);
                        }

                    // paid status
                    } elseif ($invoice['closed_on'] !== null && $invoice['is_canceled'] === false) {
                        $invoice['status'] = self::STATUS_FILTER_PAID;
                        $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_PAID);

                    // canceled status
                    } elseif ($invoice['closed_on'] !== null && $invoice['is_canceled'] === true) {
                        $invoice['status'] = self::STATUS_FILTER_CANCELED;
                        $invoice['status_lang'] = $this->getStatusLangValue(self::STATUS_FILTER_CANCELED);
                    } else {
                        $invoice['status'] = 'unknown';
                    }

                    $invoice_ids[] = $invoice['id'];
                    $invoices[] = $invoice;
                }

                $companies = count($companies) ? Companies::getIdNameMap(array_keys($companies)) : null;
            }
        }

        if (empty($invoices)) {
            $invoices = null;
        } else {
            $related_invoice_projects = AngieApplication::getContainer()
                ->get(RelatedInvoiceProjectsResolverInterface::class)
                    ->resolveForInvoices($invoice_ids);

            foreach (array_keys($invoices) as $invoices_key) {
                $this->prepareRecordDetails(
                    $invoices[$invoices_key],
                    $related_invoice_projects,
                    $companies
                );
            }
        }

        return [
            $invoices,
            $companies,
        ];
    }

    protected function prepareConditions(User $user, array $additional = null): string
    {
        $conditions = [
            DB::prepare('is_trashed = ?', false),
        ];

        if ($this->getProjectFilter() != Projects::PROJECT_FILTER_ANY) {
            $sql = DB::prepare('SELECT i.id
                    FROM invoices i
                    LEFT JOIN invoice_items ii ON ii.parent_id = i.id AND ii.parent_type = ?
                    LEFT JOIN time_records tr ON tr.invoice_item_id = ii.id AND tr.invoice_type = ?
                    LEFT JOIN tasks t ON tr.parent_type = ? AND tr.parent_id = t.id
                    LEFT JOIN projects p ON t.project_id = p.id OR (tr.parent_type = ? AND tr.parent_id = p.id)
                    WHERE tr.invoice_item_id IS NOT NULL AND tr.parent_id IS NOT NULL AND i.is_trashed = ? AND p.id IN (?)
                    GROUP BY i.id',
                Invoice::class, Invoice::INVOICE_TYPE, Task::class, Project::class, false, Projects::getProjectIdsByDataFilter($this, $user));

            $invoice_ids = [0];
            if ($rows = DB::execute($sql)) {
                $rows->setCasting('id', DBResult::CAST_INT);

                foreach ($rows->toArray() as $row) {
                    $invoice_ids[] = $row['id'];
                }
            }

            $conditions[] = DB::prepare('(invoices.id IN (?))', $invoice_ids);
        }

        if ($additional && isset($additional['is_trashed'])) {
            $conditions[] = DB::prepare('(invoices.is_trashed = ?)', $additional['is_trashed']);
        }

        // Status filter
        switch ($this->getStatusFilter()) {
            case self::STATUS_FILTER_ISSUED:
                $conditions[] = DB::prepare('(invoices.issued_on IS NOT NULL AND invoices.closed_on IS NULL)');
                break;
            case self::STATUS_FILTER_OVERDUE:
                $conditions[] = DB::prepare('(invoices.issued_on IS NOT NULL AND invoices.closed_on IS NULL AND invoices.paid_amount = 0 AND invoices.due_on < DATE(?))', DateTimeValue::now()->advance(Globalization::getUserGmtOffset($user), false));
                break;
            case self::STATUS_FILTER_UNSENT:
                $conditions[] = DB::prepare('(invoices.issued_on IS NOT NULL AND invoices.closed_on IS NULL AND invoices.sent_on IS NULL AND invoices.paid_amount = 0 AND invoices.due_on >= DATE(?) AND (invoices.recipients IS NULL OR invoices.recipients = ?))', DateTimeValue::now()->advance(Globalization::getUserGmtOffset($user)), '');
                break;
            case self::STATUS_FILTER_SENT:
                $conditions[] = DB::prepare('(invoices.issued_on IS NOT NULL AND invoices.closed_on IS NULL AND invoices.sent_on IS NOT NULL AND invoices.due_on >= DATE(?))', DateTimeValue::now()->advance(Globalization::getUserGmtOffset($user)));
                break;
            case self::STATUS_FILTER_PARTIALLY_PAID:
                $conditions[] = DB::prepare('(invoices.issued_on IS NOT NULL AND invoices.closed_on IS NULL AND invoices.balance_due > 0 AND invoices.paid_amount > 0 AND invoices.due_on >= DATE(?))', DateTimeValue::now()->advance(Globalization::getUserGmtOffset($user)));
                break;
            case self::STATUS_FILTER_OVERDUE_PARTIALLY_PAID:
                $conditions[] = DB::prepare('(invoices.issued_on IS NOT NULL AND invoices.closed_on IS NULL AND invoices.balance_due > 0 AND invoices.paid_amount > 0 AND invoices.due_on < DATE(?))', DateTimeValue::now()->advance(Globalization::getUserGmtOffset($user)));
                break;
            case self::STATUS_FILTER_PAID:
                $conditions[] = DB::prepare('(invoices.closed_on IS NOT NULL AND invoices.is_canceled = ?)', false);
                break;
            case self::STATUS_FILTER_CANCELED:
                $conditions[] = DB::prepare('(invoices.closed_on IS NOT NULL AND invoices.is_canceled = ?)', true);
                break;
            case self::STATUS_FILTER_ALL_EXCEPT_CANCELED:
                $conditions[] = DB::prepare('(invoices.is_canceled = ?)', false);
                break;
        }

        if (!$this->getIncludeCreditInvoices()) {
            $conditions[] = DB::prepare('(invoices.total > ?)', [0]);
        }

        if ($this->getClientFilter() != self::CLIENT_FILTER_ANY) {
            if ($this->getClientFilter() == self::CLIENT_FILTER_CHOOSEN) {
                $conditions[] = DB::prepare('(invoices.company_id = ?)', $this->getClientId());
            } else {
                $conditions[] = DB::prepare('(invoices.company_name = ?)', $this->getClientName());
            }
        }

        $this->prepareUserFilterConditions($user, 'issued', 'invoices', $conditions, 'created_by_id');
        $this->prepareDateFilterConditions($user, 'issued', 'invoices', $conditions);
        $this->prepareDateFilterConditions($user, 'due', 'invoices', $conditions);
        $this->prepareDateFilterConditions($user, 'closed', 'invoices', $conditions);

        return implode(' AND ', $conditions);
    }

    // ---------------------------------------------------
    //  Getters and Setters
    // ---------------------------------------------------

    /**
     * Set non-field value during DataManager::create() and DataManager::update() calls.
     *
     * @param string $attribute
     * @param mixed  $value
     */
    public function setAttribute($attribute, $value)
    {
        switch ($attribute) {
            case 'status_filter':
                $this->setStatusFilter($value);
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
            case 'client_filter':
                if (str_starts_with((string) $value, self::CLIENT_FILTER_SELECTED)) {
                    $this->filterByClientName($this->getNamesFromFilterValue($value, self::CLIENT_FILTER_SELECTED)[0]);
                } elseif (str_starts_with((string) $value, self::CLIENT_FILTER_CHOOSEN)) {
                    $this->filterByClientId($this->getIdFromFilterValue($value));
                } else {
                    $this->setClientFilter(self::CLIENT_FILTER_ANY);
                }

                break;
            case 'include_credit_invoices':
                $this->setIncludeCreditInvoices((bool) $value);
                break;
            case 'issued_by_filter':
                $this->setUserFilterAttribute('issued', $value);
                break;
            case 'issued_on_filter':
                $this->setDateFilterAttribute('issued', $value);
                break;
            case 'due_on_filter':
                $this->setDateFilterAttribute('due', $value);
                break;
            case 'closed_on_filter':
                $this->setDateFilterAttribute('closed', $value);
                break;
            default:
                parent::setAttribute($attribute, $value);
        }
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['status_filter'] = $this->getStatusFilter();
        $result['include_credit_invoices'] = $this->getIncludeCreditInvoices();

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

        $result['client_filter'] = $this->getClientFilter();
        $result['client_name'] = $this->getClientName();

        $this->describeUserFilter('issued', $result);
        $this->describeDateFilter('issued', $result);
        $this->describeDateFilter('due', $result);
        $this->describeDateFilter('closed', $result);

        return $result;
    }

    /**
     * Get include credit invoices.
     *
     * @return bool
     */
    public function getIncludeCreditInvoices()
    {
        return $this->getAdditionalProperty('include_credit_invoices');
    }

    /**
     * Set include credit invoices.
     *
     * @param  bool $value
     * @return bool
     */
    public function setIncludeCreditInvoices($value)
    {
        return $this->setAdditionalProperty('include_credit_invoices', $value);
    }

    /**
     * Return user filter value.
     *
     * @return string
     */
    public function getStatusFilter()
    {
        return $this->getAdditionalProperty('status_filter', self::STATUS_FILTER_ANY);
    }

    /**
     * Set user filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setStatusFilter($value)
    {
        return $this->setAdditionalProperty('status_filter', $value);
    }

    /**
     * Return client filter.
     *
     * @return string
     */
    public function getClientFilter()
    {
        return $this->getAdditionalProperty('client_filter', self::CLIENT_FILTER_ANY);
    }

    /**
     * Set client filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setClientFilter($value)
    {
        return $this->setAdditionalProperty('client_filter', $value);
    }

    /**
     * Return client name.
     *
     * @return string
     */
    public function getClientName()
    {
        return (string) $this->getAdditionalProperty('client_name');
    }

    /**
     * Set client filter name.
     *
     * @param  $value
     * @return string
     */
    public function setClientName($value)
    {
        return $this->setAdditionalProperty('client_name', $value);
    }

    /**
     * Return client id.
     *
     * @return string
     */
    public function getClientId()
    {
        return (string) $this->getAdditionalProperty('client_id');
    }

    /**
     * Set client filter id.
     *
     * @param  $value
     * @return string
     */
    public function setClientId($value)
    {
        return $this->setAdditionalProperty('client_id', $value);
    }

    /**
     * Set filter to filter records by $client_name.
     *
     * @param $client_name
     */
    public function filterByClientName($client_name)
    {
        $this->setClientFilter(self::CLIENT_FILTER_SELECTED);
        $this->setAdditionalProperty('client_name', (string) $client_name);
    }

    /**
     * Set filter to filter records by $client_id.
     *
     * @param $client_id
     */
    public function filterByClientId($client_id)
    {
        $this->setClientFilter(self::CLIENT_FILTER_CHOOSEN);
        $this->setAdditionalProperty('client_id', (string) $client_id);
    }

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
     * Set filter to filter records by project client.
     *
     * @param  int $project_client_id
     * @return int
     */
    public function filterByProjectClient($project_client_id)
    {
        $this->setProjectFilter(Projects::PROJECT_FILTER_CLIENT);
        if ($project_client_id instanceof Company) {
            $this->setAdditionalProperty('project_client_id', $project_client_id->getId());
        } else {
            $this->setAdditionalProperty('project_client_id', (int) $project_client_id);
        }
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
     * Return project ID-s.
     *
     * @return array
     */
    public function getProjectIds()
    {
        return $this->getAdditionalProperty('project_ids');
    }

    /**
     * Return user filter value.
     *
     * @return string
     */
    public function getIssuedByFilter()
    {
        return $this->getAdditionalProperty('issued_by_filter', DataFilter::USER_FILTER_ANYBODY);
    }

    /**
     * Set user filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setIssuedByFilter($value)
    {
        return $this->setAdditionalProperty('issued_by_filter', $value);
    }

    /**
     * Set filter by company values.
     *
     * @param int $company_id
     */
    public function issuedByCompanyMember($company_id)
    {
        $this->setIssuedByFilter(DataFilter::USER_FILTER_COMPANY_MEMBER);
        $this->setAdditionalProperty('issued_by_company_member_id', $company_id);
    }

    /**
     * Return company ID set for user filter.
     *
     * @return int
     */
    public function getIssuedByCompanyMember()
    {
        return $this->getAdditionalProperty('issued_by_company_member_id');
    }

    /**
     * Set user filter to filter only tracked object for selected users.
     *
     * @param array $users
     */
    public function issuedByUsers($users)
    {
        $this->setIssuedByFilter(DataFilter::USER_FILTER_SELECTED);

        if (is_array($users)) {
            $user_ids = [];

            foreach ($users as $k => $v) {
                $user_ids[$k] = $v instanceof User ? $v->getId() : (int) $v;
            }
        } else {
            $user_ids = null;
        }

        $this->setAdditionalProperty('issued_by_selected_users', $user_ids);
    }

    /**
     * Return array of selected users.
     *
     * @return array
     */
    public function getIssuedByUsers()
    {
        return $this->getAdditionalProperty('issued_by_selected_users');
    }

    /**
     * Return issued date filter value.
     *
     * @return string
     */
    public function getIssuedOnFilter()
    {
        return $this->getAdditionalProperty('issued_on_filter', DataFilter::DATE_FILTER_ANY);
    }

    /**
     * Set issued date filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setIssuedOnFilter($value)
    {
        return $this->setAdditionalProperty('issued_on_filter', $value);
    }

    /**
     * @return int
     */
    public function getIssuedAge()
    {
        return (int) $this->getAdditionalProperty('issued_age');
    }

    /**
     * Set issued on age.
     *
     * @param  int    $value
     * @param  string $filter
     * @return string
     */
    public function issuedAge($value, $filter = DataFilter::DATE_FILTER_AGE_IS)
    {
        if ($filter == DataFilter::DATE_FILTER_AGE_IS || DataFilter::DATE_FILTER_AGE_IS_LESS_THAN || $filter == DataFilter::DATE_FILTER_AGE_IS_MORE_THAN) {
            $this->setIssuedOnFilter($filter);
        } else {
            throw new InvalidParamError('filter', $filter);
        }

        return $this->setAdditionalProperty('issued_age', (int) $value);
    }

    /**
     * Filter invoices that are issued on before given date.
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function issuedBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setIssuedOnFilter($filter);
        $this->setAdditionalProperty('issued_on_filter_on', (string) $date);
    }

    /**
     * Filter invoices that are issued on after given date.
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function issuedAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setIssuedOnFilter($filter);
        $this->setAdditionalProperty('issued_on_filter_on', (string) $date);
    }

    /**
     * Filter invoices that are issued on a given date.
     *
     * @param string $date
     */
    public function issuedOnDate($date)
    {
        $this->setIssuedOnFilter(DataFilter::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('issued_on_filter_on', (string) $date);
    }

    /**
     * Return issued on filter value.
     *
     * @return DateValue
     */
    public function getIssuedOnDate()
    {
        $on = $this->getAdditionalProperty('issued_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Return invoices that are issued in a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function issuedInRange($from, $to)
    {
        $this->setIssuedOnFilter(DataFilter::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('issued_on_filter_from', (string) $from);
        $this->setAdditionalProperty('issued_on_filter_to', (string) $to);
    }

    /**
     * Return issued on filter range.
     *
     * @return array
     */
    public function getIssuedInRange()
    {
        $from = $this->getAdditionalProperty('issued_on_filter_from');
        $to = $this->getAdditionalProperty('issued_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    /**
     * Return due date filter value.
     *
     * @return string
     */
    public function getDueOnFilter()
    {
        return $this->getAdditionalProperty('due_on_filter', DataFilter::DATE_FILTER_ANY);
    }

    /**
     * Set due date filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setDueOnFilter($value)
    {
        return $this->setAdditionalProperty('due_on_filter', $value);
    }

    /**
     * Filter invoices that are due on a given date.
     *
     * @param string $date
     */
    public function dueOnDate($date)
    {
        $this->setDueOnFilter(DataFilter::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('due_on_filter_on', (string) $date);
    }

    /**
     * Return due on filter value.
     *
     * @return DateValue
     */
    public function getDueOnDate()
    {
        $on = $this->getAdditionalProperty('due_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Return invoices that are due in a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function dueInRange($from, $to)
    {
        $this->setDueOnFilter(DataFilter::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('due_on_filter_from', (string) $from);
        $this->setAdditionalProperty('due_on_filter_to', (string) $to);
    }

    /**
     * Return due on filter range.
     *
     * @return array
     */
    public function getDueInRange()
    {
        $from = $this->getAdditionalProperty('due_on_filter_from');
        $to = $this->getAdditionalProperty('due_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    /**
     * Filter invoices that are due on before given date.
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function dueBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setDueOnFilter($filter);
        $this->setAdditionalProperty('due_on_filter_on', (string) $date);
    }

    /**
     * Filter invoices that are due on after given date.
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function dueAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setDueOnFilter($filter);
        $this->setAdditionalProperty('due_on_filter_on', (string) $date);
    }

    /**
     * Return closed date filter value.
     *
     * @return string
     */
    public function getClosedOnFilter()
    {
        return $this->getAdditionalProperty('closed_on_filter', DataFilter::DATE_FILTER_ANY);
    }

    /**
     * Set closed date filter value.
     *
     * @param  string $value
     * @return string
     */
    public function setClosedOnFilter($value)
    {
        return $this->setAdditionalProperty('closed_on_filter', $value);
    }

    /**
     * Filter invoices that are closed on a given date.
     *
     * @param string $date
     */
    public function closedOnDate($date)
    {
        $this->setClosedOnFilter(DataFilter::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('closed_on_filter_on', (string) $date);
    }

    /**
     * Return closed on filter value.
     *
     * @return DateValue
     */
    public function getClosedOnDate()
    {
        $on = $this->getAdditionalProperty('closed_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Filter invoices that are closed on before given date.
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function closedBeforeDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_BEFORE_AND_ON_SELECTED_DATE : self::DATE_FILTER_BEFORE_SELECTED_DATE;
        $this->setClosedOnFilter($filter);
        $this->setAdditionalProperty('closed_on_filter_on', (string) $date);
    }

    /**
     * Filter invoices that are closed on after given date.
     *
     * @param string $date
     * @param bool   $inclusive
     */
    public function closedAfterDate($date, $inclusive = false)
    {
        $filter = $inclusive ? self::DATE_FILTER_AFTER_AND_ON_SELECTED_DATE : self::DATE_FILTER_AFTER_SELECTED_DATE;
        $this->setClosedOnFilter($filter);
        $this->setAdditionalProperty('closed_on_filter_on', (string) $date);
    }

    /**
     * Return invoices that are closed in a given range.
     *
     * @param string $from
     * @param string $to
     */
    public function closedInRange($from, $to)
    {
        $this->setClosedOnFilter(DataFilter::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('closed_on_filter_from', (string) $from);
        $this->setAdditionalProperty('closed_on_filter_to', (string) $to);
    }

    /**
     * Return closed on filter range.
     *
     * @return array
     */
    public function getClosedInRange()
    {
        $from = $this->getAdditionalProperty('closed_on_filter_from');
        $to = $this->getAdditionalProperty('closed_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
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

    // ---------------------------------------------------
    //  Group by
    // ---------------------------------------------------

    const GROUP_BY_STATUS = 'status';
    const GROUP_BY_PROJECT = 'project';
    const GROUP_BY_CLIENT = 'client';
    const GROUP_BY_ISSUED_ON = 'issued_on';
    const GROUP_BY_DUE_ON = 'due_on';
    const GROUP_BY_CLOSED_ON = 'closed_on';

    public function canBeGroupedBy(): array
    {
        return [
            self::GROUP_BY_STATUS,
            self::GROUP_BY_PROJECT,
            self::GROUP_BY_CLIENT,
            self::GROUP_BY_ISSUED_ON,
            self::GROUP_BY_DUE_ON,
            self::GROUP_BY_CLOSED_ON,
        ];
    }

    public function getGroupingMaxLevel(): int
    {
        return 2;
    }

    public function canRun(User $user): bool
    {
        return $user->isFinancialManager();
    }
}
