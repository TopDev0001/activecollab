<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Invoice based on tracking report result.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage models
 */
trait IInvoiceBasedOnTrackingFilterResultImplementation
{
    use IInvoiceBasedOnTrackedDataImplementation;

    /**
     * Query tracking records.
     *
     * This function returns three elements: array of time records, array of expenses and project
     *
     * @return array
     * @throws Error
     */
    public function queryRecordsForNewInvoice(IUser $user = null)
    {
        $report = $this->prepareReportForInvoiceBasedOn();

        if ($report instanceof TrackingFilter) {
            if ($report_results = $report->run($user)) {
                $time_record_ids = [];
                $expense_ids = [];

                foreach ($report_results['all']['records'] as $result) {
                    if ($result['billable_status'] == ITrackingObject::BILLABLE) { // use only billable
                        if ($result['type'] == 'TimeRecord') {
                            $time_record_ids[] = $result['id'];
                        } elseif ($result['type'] == 'Expense') {
                            $expense_ids[] = $result['id'];
                        }
                    }
                }

                $time_records = count($time_record_ids) ? TimeRecords::findByIds($time_record_ids) : null;
                $expenses = count($expense_ids) ? Expenses::findByIds($expense_ids) : null;

                return [$time_records, $expenses];
            } else {
                return [null, null];
            }
        }

        throw new Error('Failed to prepare report');
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return report result.
     *
     * @return TrackingFilter
     */
    abstract public function prepareReportForInvoiceBasedOn();
}
