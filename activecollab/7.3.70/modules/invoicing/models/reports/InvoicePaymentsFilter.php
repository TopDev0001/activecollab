<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Invoicing\Utils\RelatedInvoiceProjects\Resolver\RelatedInvoiceProjectsResolverInterface;
use Angie\Globalization;

class InvoicePaymentsFilter extends DataFilter
{
    // Client filter
    const CLIENT_FILTER_ANYBODY = 'anybody';
    const CLIENT_FILTER_SELECTED = 'selected';

    // Group
    const GROUP_BY_COMPANY = 'company';
    const GROUP_BY_DATE = 'date';
    const GROUP_BY_MONTH = 'month';
    const GROUP_BY_YEAR = 'year';

    public function run(User $user, array $additional = null): ?array
    {
        $rows = $this->queryPayments($user);

        if ($rows) {
            $rows = $rows->toArrayIndexedBy('id');

            $this->populateParentInfo($rows);

            $group_by = $this->getGroupBy();

            // Group by and return
            switch (array_shift($group_by)) {
                case self::GROUP_BY_DATE:
                    return $this->groupByDate($rows, $user);
                case self::GROUP_BY_MONTH:
                    return $this->groupByMonth($rows, $user);
                case self::GROUP_BY_YEAR:
                    return $this->groupByYear($rows);
                case self::GROUP_BY_COMPANY:
                    return $this->groupByCompany($rows);
                default:
                    return [
                        'all' => [
                            'label' => lang('All Payments'),
                            'payments' => $rows,
                        ],
                    ];
            }
        }

        return null;
    }

    /**
     * @return DbResult|null
     */
    private function &queryPayments(User $user)
    {
        try {
            $conditions = $this->prepareConditions($user);
        } catch (DataFilterConditionsError $e) {
            $result = null;

            return $result; // Invalid conditions, no payments can match them
        }

        if ($conditions) {
            $query = "SELECT * FROM `payments` WHERE $conditions ORDER BY `paid_on` DESC";
        } else {
            $query = 'SELECT * FROM `payments` ORDER BY `paid_on` DESC';
        }

        if ($rows = DB::execute($query)) {
            $rows->setCasting(
                [
                    'created_on' => DBResult::CAST_DATE,
                    'paid_on' => DBResult::CAST_DATE,
                    'amount' => DBResult::CAST_FLOAT,
                ]
            );
        }

        return $rows;
    }

    public function prepareConditions(User $user): string
    {
        $conditions = [
            DB::prepare('(parent_type = ?)', Invoice::class),
        ];

        $this->prepareDateFilterConditions($user, 'paid', 'payments', $conditions);

        if ($this->getCompanyFilter() === self::CLIENT_FILTER_SELECTED) {
            $company = DataObjectPool::get('Company', $this->getCompanyId());

            if ($company instanceof Company) {
                $conditions[] = DB::prepare('(payments.parent_id IN (SELECT id FROM invoices WHERE company_id = ?))', $company->getId());
            } else {
                throw new DataFilterConditionsError('company_filter', self::CLIENT_FILTER_SELECTED, 'Company does not exist');
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Return company filter value.
     *
     * @return string
     */
    public function getCompanyFilter()
    {
        return $this->getAdditionalProperty('company_filter', self::CLIENT_FILTER_ANYBODY);
    }

    /**
     * Return company value.
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->getAdditionalProperty('company_id');
    }

    private function populateParentInfo(array & $rows): void
    {
        $invoices_rows = [];

        foreach ($rows as &$row) {
            if (empty($invoices_rows[$row['parent_id']])) {
                $invoices_rows[$row['parent_id']] = [
                    'number' => lang('N/A'),
                    'project_id' => 0,
                ];
            }
        }

        $currencies_name_map = Currencies::getIdNameMap();
        $currencies_name_code = Currencies::getIdCodeMap();

        $invoices_ids = array_keys($invoices_rows);
        $invoices = DB::execute(
            'SELECT `id`, `number`, `project_id`, `company_id`, `company_name`, `currency_id` FROM `invoices` WHERE `id` in (?)',
            $invoices_ids
        );

        $related_invoice_projects = AngieApplication::getContainer()
            ->get(RelatedInvoiceProjectsResolverInterface::class)
                ->resolveForInvoices($invoices_ids);

        foreach ($invoices as $key => $invoice) {
            $invoice_id = $invoice['id'];

            $currency_id = $invoice['currency_id'];
            $currency_name = isset($currencies_name_map[$currency_id]) ? $currencies_name_map[$currency_id] : null;
            $currency_code = isset($currencies_name_code[$currency_id]) ? $currencies_name_code[$currency_id] : null;

            $invoices_rows[$invoice_id] = [
                'number' => $invoice['number'],
                'project_id' => $invoice['project_id'],
                'project_name' => $related_invoice_projects->getRelatedProjectNames($invoice_id),
                'company_id' => $invoice['company_id'],
                'company_name' => $invoice['company_name'],
                'currency_id' => $invoice['currency_id'],
                'currency_name' => $currency_name,
                'currency_code' => $currency_code,
                'related_projects' => $related_invoice_projects->getRelatedProjects($invoice_id),
            ];
        }

        foreach ($rows as &$row) {
            $invoice_id = array_var($row, 'parent_id', 0, true);

            if (isset($invoices_rows[$invoice_id])) {
                $row['invoice_number'] = $invoices_rows[$invoice_id]['number'];
                $row['project_id'] = (int) $invoices_rows[$invoice_id]['project_id'];
                $row['project_name'] = $invoices_rows[$invoice_id]['project_name'];
                $row['company_id'] = (int) $invoices_rows[$invoice_id]['company_id'];
                $row['company_name'] = $invoices_rows[$invoice_id]['company_name'];
                $row['currency_id'] = (int) $invoices_rows[$invoice_id]['currency_id'];
                $row['currency_name'] = $invoices_rows[$invoice_id]['currency_name'];
                $row['currency_code'] = $invoices_rows[$invoice_id]['currency_code'];
            }

            $row['invoice_id'] = (int) $invoice_id;
        }

        unset($row); // just in case
    }

    /**
     * Group results by date.
     *
     * @param  array $rows
     * @return array
     */
    protected function groupByDate($rows, IUser $user)
    {
        $result = [];

        foreach ($rows as $row) {
            $created_date = $row['paid_on'] instanceof DateValue
                ? $row['paid_on']->formatForUser($user, 0)
                : lang('Unknown Date');

            if (empty($result[$created_date])) {
                $result[$created_date] = ['label' => $created_date, 'payments' => []];
            }

            $result[$created_date]['payments'][$row['id']] = $row;
        }

        return $result;
    }

    /**
     * Group rows by month.
     *
     * @param  array $rows
     * @return array
     */
    protected function groupByMonth($rows, IUser $user)
    {
        $result = [];

        $months = Globalization::getMonthNames($user->getLanguage());

        foreach ($rows as $row) {
            $created_date = $row['paid_on'] instanceof DateValue ? $months[$row['paid_on']->getMonth()] . ', ' . $row['paid_on']->getYear() : lang('Unknown Month');

            if (empty($result[$created_date])) {
                $result[$created_date] = ['label' => $created_date, 'payments' => []];
            }

            $result[$created_date]['payments'][$row['id']] = $row;
        }

        return $result;
    }

    /**
     * Group rows by year.
     *
     * @param  array $rows
     * @return array
     */
    protected function groupByYear($rows)
    {
        $result = [];

        foreach ($rows as $row) {
            $created_date = $row['paid_on'] instanceof DateValue ? (string) $row['paid_on']->getYear() : lang('Unknown Year');

            if (empty($result[$created_date])) {
                $result[$created_date] = ['label' => $created_date, 'payments' => []];
            }

            $result[$created_date]['payments'][$row['id']] = $row;
        }

        return $result;
    }

    /**
     * Group rows by client company.
     *
     * @param  array $rows
     * @return array
     */
    protected function groupByCompany($rows)
    {
        $result = [];

        foreach ($rows as $row) {
            $key = $row['company_id'] ? 'company-' . $row['company_id'] : 'unknown-company';

            if (empty($result[$key])) {
                $result[$key] = [
                    'label' => $row['company_name'] ? $row['company_name'] : lang('Unknown Client'),
                    'payments' => [],
                ];
            }

            $result[$key]['payments'][$row['id']] = $row;
        }

        return $result;
    }

    /**
     * Return export columns.
     */
    public function getExportColumns(): array
    {
        return [
            'Payment ID',
            'Amount',
            'Created On',
            'Paid On',
            'Currency Name',
            'Currency Code',
            'Company ID',
            'Company Name',
            'Invoice ID',
            'Invoice Number',
            'Project ID',
            'Project Name',
            'Comment',
        ];
    }

    public function exportWriteLines(User $user, array $result): void
    {
        foreach ($result as $k => $v) {
            if ($v['payments'] && is_foreachable($v['payments'])) {
                foreach ($v['payments'] as $payment) {
                    $this->exportWriteLine(
                        [
                            $payment['id'],
                            $payment['amount'],
                            $payment['created_on'] instanceof DateValue ? $payment['created_on']->toMySQL() : null,
                            $payment['paid_on'] instanceof DateValue ? $payment['paid_on']->toMySQL() : null,
                            $payment['currency_name'],
                            $payment['currency_code'],
                            $payment['company_id'],
                            $payment['company_name'],
                            $payment['invoice_id'],
                            $payment['invoice_number'],
                            $payment['project_id'],
                            $payment['project_name'],
                            $payment['comment'],
                        ]
                    );
                }
            }
        }
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $this->describeDateFilter('paid', $result);

        $result['company_filter'] = $this->getCompanyFilter();

        if ($result['company_filter'] === self::CLIENT_FILTER_SELECTED) {
            $result['company_id'] = $this->getCompanyId();
        }

        return $result;
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
            case 'paid_on_filter':
                $this->setDateFilterAttribute('paid', $value);
                break;
            case 'company_filter':
                if (str_starts_with($value, self::CLIENT_FILTER_SELECTED)) {
                    $this->filterByCompany($this->getIdFromFilterValue($value));
                } else {
                    $this->setCompanyFilter($value);
                }

                break;
            default:
                parent::setAttribute($attribute, $value);
        }
    }

    /**
     * Set filter by company values.
     *
     * @param int $company_id
     */
    public function filterByCompany($company_id)
    {
        $this->setCompanyFilter(self::CLIENT_FILTER_SELECTED);
        $this->setAdditionalProperty('company_id', $company_id);
    }

    /**
     * Set company filter to a given $value.
     *
     * @param  string $value
     * @return string
     */
    public function setCompanyFilter($value)
    {
        return $this->setAdditionalProperty('company_filter', $value);
    }

    /**
     * Return paid on filter value.
     *
     * @return string
     */
    public function getPaidOnFilter()
    {
        return $this->getAdditionalProperty('issued_on_filter', self::DATE_FILTER_ANY);
    }

    /**
     * Filter objects tracked for a given date.
     *
     * @param string $date
     */
    public function paidOnDate($date)
    {
        $this->setPaidOnFilter(self::DATE_FILTER_SELECTED_DATE);
        $this->setAdditionalProperty('paid_on_filter_on', (string) $date);
    }

    /**
     * Set paid on filter to a given $value.
     *
     * @param  string $value
     * @return string
     */
    public function setPaidOnFilter($value)
    {
        return $this->setAdditionalProperty('issued_on_filter', $value);
    }

    /**
     * Return selected date for paid on filter.
     *
     * @return DateValue
     */
    public function getPaidOnDate()
    {
        $on = $this->getAdditionalProperty('paid_on_filter_on');

        return $on ? new DateValue($on) : null;
    }

    /**
     * Filter payments by date range.
     *
     * @param string $from
     * @param string $to
     */
    public function paidInRange($from, $to)
    {
        $this->setPaidOnFilter(self::DATE_FILTER_SELECTED_RANGE);
        $this->setAdditionalProperty('paid_on_filter_from', (string) $from);
        $this->setAdditionalProperty('paid_on_filter_to', (string) $to);
    }

    /**
     * Return selected range for date filter.
     *
     * @return array
     */
    public function getPaidInRange()
    {
        $from = $this->getAdditionalProperty('paid_on_filter_from');
        $to = $this->getAdditionalProperty('paid_on_filter_to');

        return $from && $to ? [new DateValue($from), new DateValue($to)] : [null, null];
    }

    /**
     * Filter payments by year.
     *
     * @param string $year
     */
    public function paidInYear($year)
    {
        $this->setPaidOnFilter(self::DATE_FILTER_SELECTED_YEAR);
        $this->setAdditionalProperty('paid_on_filter_year', (int) $year);
    }

    /**
     * @return int
     */
    public function getPaidInYear()
    {
        return $this->getAdditionalProperty('paid_on_filter_year');
    }

    public function canBeGroupedBy(): array
    {
        return [
            self::GROUP_BY_COMPANY,
            self::GROUP_BY_DATE,
            self::GROUP_BY_MONTH,
            self::GROUP_BY_YEAR,
        ];
    }

    public function canRun(User $user): bool
    {
        return $user->isFinancialManager();
    }

    public function canEdit(User $user): bool
    {
        return $user->isFinancialManager();
    }

    public function canDelete(User $user): bool
    {
        return $user->isFinancialManager();
    }
}
