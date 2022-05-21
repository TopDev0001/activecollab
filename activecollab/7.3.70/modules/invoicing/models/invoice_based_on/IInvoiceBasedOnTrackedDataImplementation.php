<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Invoice based on tracked data.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage models
 */
trait IInvoiceBasedOnTrackedDataImplementation
{
    use IInvoiceBasedOnImplementation;

    /**
     * Create new invoice instance based on parent object.
     *
     * @param  string               $number
     * @param  string               $client_address
     * @param  array|null           $additional
     * @return Invoice
     * @throws ConfigOptionDnxError
     * @throws Error
     * @throws Exception
     * @throws InvalidInstanceError
     * @throws InvalidParamError
     */
    public function createInvoice($number, Company $client = null, $client_address = null, $additional = null, IUser $user = null)
    {
        if (empty($client)) {
            throw new InvalidInstanceError('client', $client, 'Company');
        }

        $additional = $additional ? (array) $additional : [];

        [$time_records, $expenses] = $this->queryRecordsForNewInvoice($user);

        $invoice = $this->createInvoiceFromPropeties($number, $client, $client_address, $additional);

        $sum_by = array_var($additional, 'sum_by');

        if (empty($sum_by)) {
            $sum_by = ConfigOptions::getValue('on_invoice_based_on');
        }

        $items = $this->prepareItemsForInvoice($time_records, $expenses, $sum_by, array_var($additional, 'first_tax_rate_id'), array_var($additional, 'second_tax_rate_id'), $user);

        if ($items && is_foreachable($items)) {
            return $this->commitInvoiceItems($items, $invoice);
        } else {
            throw new Error('Invoice must have at least one item. Make sure that selected records are not already pending payment in another invoice.');
        }
    }

    /**
     * Query tracking records.
     *
     * This function returns three elements: array of time records, array of expenses and project
     *
     * @return array
     */
    abstract public function queryRecordsForNewInvoice(IUser $user = null);

    /**
     * Create invoice items for object.
     *
     * @param  TimeRecord[]      $time_records
     * @param  Expense[]         $expenses
     * @param  string            $sum_by
     * @param  TaxRate           $first_tax_rate
     * @param  TaxRate           $second_tax_rate
     * @return array
     * @throws InvalidParamError
     */
    protected function prepareItemsForInvoice($time_records, $expenses, $sum_by, $first_tax_rate, $second_tax_rate, IUser $user)
    {
        if ($time_records || $expenses) {
            switch ($sum_by) {
                case Invoice::INVOICE_SETTINGS_SUM_ALL:
                    return $this->sumAllRecordsForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate);
                case Invoice::INVOICE_SETTINGS_SUM_ALL_BY_TASK:
                    return $this->sumRecordsGroupedByTaskForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate);
                case Invoice::INVOICE_SETTINGS_SUM_ALL_BY_PROJECT:
                    return $this->sumRecordsGroupedByProjectForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate);
                case Invoice::INVOICE_SETTINGS_SUM_ALL_BY_JOB_TYPE:
                    return $this->sumRecordsGroupedByJobTypeForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate);
                case Invoice::INVOICE_SETTINGS_KEEP_AS_SEPARATE:
                    return $this->keepRecordsSeparatedForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate);
                default:
                    throw new InvalidParamError('sum_by', $sum_by);
            }
        } else {
            return null;
        }
    }

    /**
     * Sum all records as a single line.
     *
     * @param  TimeRecord[] $timerecords
     * @param  Expense[]    $expenses
     * @param  TaxRate      $first_tax_rate
     * @param  TaxRate      $second_tax_rate
     * @return array
     */
    private function sumAllRecordsForNewInvoice($timerecords, $expenses, $first_tax_rate, $second_tax_rate)
    {
        $items = [];
        $timerecord_ids = [];
        $expenses_ids = [];
        $total_time = 0;
        $total_expense = 0;

        $is_identical = TimeRecords::isIdenticalJobRate($timerecords);
        $unit_cost = $is_identical;

        foreach ($timerecords as $timerecord) {
            $job_type = $timerecord->getJobType();
            $timerecord_ids[] = $timerecord->getId();
            if ($timerecord->getValue() > 0 && $job_type instanceof JobType) {
                if ($is_identical) {
                    $total_time += $timerecord->getValue();
                } else {
                    $time_record_project = $timerecord->getProject();

                    $total_time = 1;
                    $unit_cost += $job_type->getHourlyRateFor($time_record_project) * $timerecord->getValue();
                }
            }
        }

        if ($total_time > 0) {
            if ($this instanceof TrackingFilter) {
                if ($is_identical) {
                    $description = lang('Total of :hours logged', ['hours' => $total_time]);
                } else {
                    $description = lang('Total time logged');
                }
            } else {
                $description = $this->getVerboseType() . ':' . $this->getName();
            }

            $items[] = [
                'description' => $description,
                'unit_cost' => $unit_cost,
                'quantity' => $total_time,
                'first_tax_rate_id' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getId() : $first_tax_rate,
                'second_tax_rate_id' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getId() : $second_tax_rate,
                'time_record_ids' => $timerecord_ids,
                'expense_ids' => [],
            ];
        }

        if (!empty($expenses)) {
            foreach ($expenses as $expense) {
                if ($expense->getValue() > 0) {
                    $total_expense += $expense->getValue();
                    $expenses_ids[] = $expense->getId();
                }
            }
        }

        if ($total_expense > 0) {
            $items[] = [
                'description' => lang('Other expenses'),
                'unit_cost' => $total_expense,
                'quantity' => 1,
                'first_tax_rate_id' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getId() : $first_tax_rate,
                'second_tax_rate_id' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getId() : $second_tax_rate,
                'time_record_ids' => [],
                'expense_ids' => $expenses_ids,
            ];
        }

        return $items;
    }

    abstract public function getVerboseType(bool $lowercase = false, Language $language = null): string;

    /**
     * Return object name.
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Sum all records by task.
     *
     * @param  TimeRecord[] $time_records
     * @param  Expense[]    $expenses
     * @param  TaxRate      $first_tax_rate
     * @param  TaxRate      $second_tax_rate
     * @return array
     */
    private function sumRecordsGroupedByTaskForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate)
    {
        $items = [];

        if (!empty($time_records)) {
            [$tasks, $projects] = $this->getParentDataMapsForRecords($time_records);

            if ($time_records && is_foreachable($time_records)) {
                $grouped_records = [];

                $job_types = JobTypes::getIdNameMap(true);

                foreach ($time_records as $time_record) {
                    $group_key = $this->getGroupKeyForGroupByTask($time_record);
                    $parent_id = $time_record->getParentId();

                    if (!isset($grouped_records[$group_key])) {
                        if ($time_record->getParentType() == Task::class) {
                            $description = Invoices::generateTaskDescription(
                                [
                                    'job_type' => isset($job_types[$time_record->getJobTypeId()])
                                        ? $job_types[$time_record->getJobTypeId()]
                                        : '',
                                    'task_number' => $tasks[$parent_id]['task_number'],
                                    'task_summary' => $tasks[$parent_id]['name'],
                                    'project_name' => $tasks[$parent_id]['project_name'],
                                ],
                            );
                        } elseif ($time_record->getParentType() == 'Project') {
                            $description = Invoices::generateProjectDescription(['name' => $projects[$parent_id]['name']]);
                        } else {
                            $description = $time_record->getParent() instanceof ApplicationObject
                                ? $time_record->getParent()->getName()
                                : lang('Unknown record');
                        }

                        $grouped_records[$group_key] = [
                            'description' => $description,
                            'time_records' => [],
                        ];
                    }

                    $grouped_records[$group_key]['time_records'][] = $time_record;
                }

                ksort($grouped_records, SORT_NATURAL);

                // Prepare items based on grouped records
                foreach ($grouped_records as $group) {
                    $this->sumGroupedTimeRecordsForNewInvoice(
                        $items,
                        $group['description'],
                        $group['time_records'],
                        $first_tax_rate,
                        $second_tax_rate,
                    );
                }
            }
        }
        $this->sumExpensesForNewInvoice($items, $expenses, $first_tax_rate, $second_tax_rate);

        return $items;
    }

    /**
     * Get detail maps that we need in order to properly format descriptions.
     *
     * @param  TimeRecord[] $time_records
     * @return array
     */
    private function getParentDataMapsForRecords($time_records)
    {
        $project_ids = [];
        $task_ids = [];

        foreach ($time_records as $time_record) {
            if ($time_record->getParentType() == 'Task') {
                $task_ids[] = $time_record->getParentId();
            } else {
                $project_ids[] = $time_record->getParentId();
            }
        }

        $task_ids = array_unique($task_ids);

        $tasks = [];

        if (count($task_ids)) {
            if ($task_rows = DB::execute('SELECT id, project_id, name, task_number FROM tasks WHERE id IN (?) AND is_trashed = ?', $task_ids, false)) {
                $task_project_ids = [];

                foreach ($task_rows as $task_row) {
                    $tasks[$task_row['id']] = $task_row;
                    $task_project_ids[] = $task_row['project_id'];
                }
            }

            $task_project_ids = DB::executeFirstColumn('SELECT DISTINCT(project_id) FROM tasks WHERE id IN (?)', $task_ids);

            if ($task_project_ids && is_foreachable($task_project_ids)) {
                $project_ids = array_merge($project_ids, $task_project_ids);
            }
        }

        $project_ids = array_unique($project_ids);

        $projects = [];

        if ($project_ids) {
            if ($project_rows = DB::execute('SELECT id, name FROM projects WHERE id IN (?) AND is_trashed = ?', $project_ids, false)) {
                foreach ($project_rows as $project_row) {
                    $projects[(int) $project_row['id']] = ['name' => $project_row['name']];
                }
            }
        }

        foreach ($tasks as $task_id => $task_details) {
            $project_id = $task_details['project_id'];

            $tasks[$task_id]['project_name'] = isset($projects[$project_id]) ? $projects[$project_id]['name'] : lang('Unknown');
        }

        return [$tasks, $projects, JobTypes::getIdNameMap()];
    }

    /**
     * Prepare and return group key for group by task method.
     *
     * @return string
     */
    private function getGroupKeyForGroupByTask(TimeRecord $time_record)
    {
        if ($time_record->getParentType() == 'Task') {
            $parent = $time_record->getParent();

            if ($parent instanceof Task) {
                return $parent->getProjectId() . '-' . $time_record->getParentId();
            }
        }

        return (string) $time_record->getParentId();
    }

    /**
     * Sum expenses and add them as a single item.
     *
     * @param array        $items
     * @param string       $group_description
     * @param TimeRecord[] $time_records
     * @param TaxRate      $first_tax_rate
     * @param TaxRate      $second_tax_rate
     */
    private function sumGroupedTimeRecordsForNewInvoice(&$items, $group_description, $time_records, $first_tax_rate, $second_tax_rate)
    {
        $total_time = 0;
        $time_record_ids = [];

        // Get identical cost, or FALSE if time records have different hourly rate
        $unit_cost = TimeRecords::isIdenticalJobRate($time_records);

        if ($unit_cost === false) {
            $total_time = 1;

            $unit_cost = 0;

            foreach ($time_records as $time_record) {
                $unit_cost += $time_record->calculateExpense();
                $time_record_ids[] = $time_record->getId();
            }
        } else {
            foreach ($time_records as $time_record) {
                $total_time += $time_record->getValue();
                $time_record_ids[] = $time_record->getId();
            }
        }

        if ($total_time > 0) {
            $items[] = [
                'description' => $group_description,
                'unit_cost' => $unit_cost,
                'quantity' => $total_time,
                'first_tax_rate_id' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getId() : $first_tax_rate,
                'second_tax_rate_id' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getId() : $second_tax_rate,
                'time_record_ids' => $time_record_ids,
                'expense_ids' => [],
            ];
        }
    }

    /**
     * Sum expenses and add them as a single item.
     *
     * @param array     $items
     * @param Expense[] $expenses
     * @param TaxRate   $first_tax_rate
     * @param TaxRate   $second_tax_rate
     */
    private function sumExpensesForNewInvoice(&$items, $expenses, $first_tax_rate, $second_tax_rate)
    {
        if ($expenses && is_foreachable($expenses)) {
            $total_expense = 0;
            $expenses_ids = [];

            foreach ($expenses as $expense) {
                if ($expense->getValue() > 0) {
                    $total_expense += $expense->getValue();
                    $expenses_ids[] = $expense->getId();
                }
            }

            if ($total_expense > 0) {
                $items[] = [
                    'description' => lang('Other expenses'),
                    'unit_cost' => $total_expense,
                    'quantity' => 1,
                    'first_tax_rate_id' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getId() : $first_tax_rate,
                    'second_tax_rate_id' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getId() : $second_tax_rate,
                    'expense_ids' => $expenses_ids,
                ];
            }
        }
    }

    /**
     * Sum all records by project.
     *
     * @param  TimeRecord[] $time_records
     * @param  Expense[]    $expenses
     * @param  TaxRate      $first_tax_rate
     * @param  TaxRate      $second_tax_rate
     * @return array
     */
    private function sumRecordsGroupedByProjectForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate)
    {
        $items = [];

        if ($time_records && is_foreachable($time_records)) {
            $grouped_records = [];

            $category_names = Categories::getIdNameMap(null, ProjectCategory::class);
            $client_names = Companies::getIdNameMap(null, STATE_ARCHIVED);

            foreach ($time_records as $time_record) {
                $time_record_parent = $time_record->getParent();

                if ($time_record_parent instanceof Project || $time_record_parent instanceof Task) {
                    $time_record_project = $time_record_parent instanceof Task ? $time_record_parent->getProject() : $time_record_parent;

                    if ($time_record_project instanceof Project) {
                        $project_id = $time_record_project->getId();

                        if (!isset($grouped_records[$project_id])) {
                            $grouped_records[$project_id] = [
                                'description' => Invoices::generateProjectDescription([
                                    'name' => $time_record_project->getName(),
                                    'category' => $time_record_project->getCategoryId() && isset($category_names[$time_record_project->getCategoryId()]) ? $category_names[$time_record_project->getCategoryId()] : '',
                                    'client' => $time_record_project->getCompanyId() && isset($client_names[$time_record_project->getCompanyId()]) ? $client_names[$time_record_project->getCompanyId()] : '',
                                ]),
                                'time_records' => [],
                            ];
                        }

                        $grouped_records[$project_id]['time_records'][] = $time_record;
                    }
                }
            }

            ksort($grouped_records); // Make sure that older projects are at the top of the list

            foreach ($grouped_records as $group) {
                $this->sumGroupedTimeRecordsForNewInvoice($items, $group['description'], $group['time_records'], $first_tax_rate, $second_tax_rate);
            }
        }

        $this->sumExpensesForNewInvoice($items, $expenses, $first_tax_rate, $second_tax_rate);

        return $items;
    }

    /**
     * Sum all records by job type.
     *
     * @param  TimeRecord[] $time_records
     * @param  Expense[]    $expenses
     * @param  TaxRate      $first_tax_rate
     * @param  TaxRate      $second_tax_rate
     * @return array
     */
    private function sumRecordsGroupedByJobTypeForNewInvoice($time_records, $expenses, $first_tax_rate, $second_tax_rate)
    {
        $items = [];

        foreach (TimeRecords::groupByJobType($time_records) as $job_type_name => $records) {
            $total_time = 0;
            $time_record_ids = [];
            $is_identical = TimeRecords::isIdenticalJobRate($records);
            $unit_cost = $is_identical;

            /** @var TimeRecord[] $records */
            foreach ($records as $record) {
                if ($record->getValue() > 0) {
                    if ($is_identical) {
                        $total_time += $record->getValue();
                    } else {
                        $job_type = $record->getJobType();

                        $total_time = 1;
                        $unit_cost += $job_type->getHourlyRateFor($record->getProject()) * $record->getValue();
                    }

                    $time_record_ids[] = $record->getId();
                }
            }

            if ($total_time > 0) {
                $items[] = [
                    'description' => Invoices::generateJobTypeDescription(['job_type' => $job_type_name]),
                    'unit_cost' => $unit_cost,
                    'quantity' => $total_time,
                    'first_tax_rate_id' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getId() : $first_tax_rate,
                    'second_tax_rate_id' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getId() : $second_tax_rate,
                    'time_record_ids' => $time_record_ids,
                    'expense_ids' => [],
                ];
            }
        }

        $this->sumExpensesForNewInvoice($items, $expenses, $first_tax_rate, $second_tax_rate);

        return $items;
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Keep all records at a single line.
     *
     * @param  TimeRecord[] $timerecords
     * @param  Expense[]    $expenses
     * @param  TaxRate      $first_tax_rate
     * @param  TaxRate      $second_tax_rate
     * @return array
     */
    private function keepRecordsSeparatedForNewInvoice($timerecords, $expenses, $first_tax_rate, $second_tax_rate)
    {
        $items = [];

        if (!empty($timerecords)) {
            foreach ($timerecords as $timerecord) {
                if ($timerecord->getValue() > 0) {
                    $job_type = $timerecord->getJobType();

                    $time_record_parent = $timerecord->getParent();

                    if ($time_record_parent instanceof Project) {
                        $time_record_project = $time_record_parent;
                    } else {
                        $time_record_project = $timerecord->getProject();
                    }

                    $items[] = [
                        'description' => Invoices::generateIndividualDescription([
                            'job_type_or_category' => $job_type->getName(),
                            'record_summary' => $timerecord->getSummary(),
                            'record_date' => $timerecord->getRecordDate()->formatDateForUser(null, 0),
                            'parent_task_or_project' => $time_record_parent instanceof Task ? '#' . $time_record_parent->getTaskNumber() . ': ' . $time_record_parent->getName() : $time_record_parent->getName(),
                            'project_name' => $time_record_project->getName(),
                        ]),
                        'unit_cost' => $job_type->getHourlyRateFor($time_record_project),
                        'quantity' => $timerecord->getValue(),
                        'first_tax_rate_id' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getId() : $first_tax_rate,
                        'second_tax_rate_id' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getId() : $second_tax_rate,
                        'time_record_ids' => [$timerecord->getId()],
                        'expense_ids' => [],
                    ];
                }
            }
        }

        // Loop throught expenses
        if (!empty($expenses)) {
            foreach ($expenses as $expense) {
                if ($expense->getValue() > 0) {
                    $expense_parent = $expense->getParent();

                    $items[] = [
                        'description' => Invoices::generateIndividualDescription([
                            'job_type_or_category' => $expense->getCategoryName(),
                            'record_summary' => $expense->getSummary(),
                            'record_date' => $expense->getRecordDate()->formatDateForUser(null, 0),
                            'parent_task_or_project' => $expense_parent instanceof Task ? '#' . $expense_parent->getTaskNumber() . ': ' . $expense_parent->getName() : $expense_parent->getName(),
                            'project_name' => $expense->getProject()->getName(),
                        ]),
                        'unit_cost' => $expense->getValue(),
                        'quantity' => 1,
                        'first_tax_rate_id' => $first_tax_rate instanceof TaxRate ? $first_tax_rate->getId() : $first_tax_rate,
                        'second_tax_rate_id' => $second_tax_rate instanceof TaxRate ? $second_tax_rate->getId() : $second_tax_rate,
                        'time_record_ids' => [],
                        'expense_ids' => [$expense->getId()],
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Return preview array of items.
     *
     * @param  array $settings
     * @return array
     */
    public function previewInvoiceItems($settings = null, IUser $user = null)
    {
        [$time_records, $expenses] = $this->queryRecordsForNewInvoice($user);

        return $this->prepareItemsForInvoice($time_records, $expenses, $settings['sum_by'], $settings['first_tax_rate_id'], $settings['second_tax_rate_id'], $user);
    }
}
