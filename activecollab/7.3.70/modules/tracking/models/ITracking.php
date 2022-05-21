<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Tracking interface.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
interface ITracking
{
    /**
     * Return default billable status for this object type.
     *
     * @return int
     */
    public function getDefaultBillableStatus();

    // ---------------------------------------------------
    //  Time
    // ---------------------------------------------------
    /**
     * Log time and return time record.
     *
     * @param  float      $value
     * @param  int        $billable_status
     * @return TimeRecord
     */
    public function trackTime($value, IUser $user, JobType $job_type, DateValue $date, $billable_status = TimeRecord::BILLABLE, IUser $by = null);

    /**
     * Returns time records attached to parent object.
     *
     * Optional filter is billable status (or array of statuses)
     *
     * @param  mixed    $billable_status
     * @return DBresult
     */
    public function getTimeRecords(User $user, $billable_status = null);

    /**
     * Returns paginated time records for user with optional date range.
     *
     * Using both 'from' and 'to' params, you will get time records that are tracked between specified datas.
     * Using just 'from', you will get time records which are tracked from specified date.
     * Using just 'to', you will get time records that are tracked until specified date.
     */
    public function getTimeRecordsCollection(
        User $user,
        int $page = 1,
        ?DateValue $from = null,
        ?DateValue $to = null
    ): TimeRecordsByParentCollection;

    /**
     * Returns values of tracked time "user vs others" with optional date range.
     * Result example:
     *   [
     *     'your_time' => 13.5,
     *     'other_time' => 27.5,
     *   ].
     */
    public function getTimeRecordsInfo(User $user, ?DateValue $from = null, ?DateValue $to = null): array;

    // ---------------------------------------------------
    //  Expenses
    // ---------------------------------------------------
    /**
     * Log time and return time record.
     *
     * @param  float      $value
     * @param  int        $billable_status
     * @return TimeRecord
     */
    public function trackExpense($value, IUser $user, ExpenseCategory $category, DateValue $date, $billable_status = Expense::BILLABLE, IUser $by = null);

    /**
     * Returns values of tracked expenses "user vs others" with optional date range.
     * Result example:
     *   [
     *     'yours' => 148000.5,
     *     'others' => 27.5,
     *   ].
     */
    public function getExpensesInfo(User $user, ?DateValue $from = null, ?DateValue $to = null): array;

    /**
     * Returns tracked expenses attached to the parent parent object.
     *
     * Optional filter is billable status (or array of statuses)
     *
     * @param  mixed    $billable_status
     * @return DBResult
     */
    public function getExpenses(User $user, $billable_status = null);

    /**
     * Return true if $user can track time for this object.
     *
     * @return bool
     */
    public function canTrackTime(User $user);

    /**
     * Return true if $user can track expenses for this object.
     *
     * @return bool
     */
    public function canTrackExpenses(User $user);

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return parent object ID.
     *
     * @return int
     */
    public function getId();
}
