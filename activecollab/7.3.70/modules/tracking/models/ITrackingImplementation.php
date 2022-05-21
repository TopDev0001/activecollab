<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

trait ITrackingImplementation
{
    public function registerITrackingImplementation(): void
    {
        $this->registerEventHandler(
            'on_before_move_to_trash',
            function ($by, $bulk) {
                $time_parent_conditions = TimeRecords::parentToCondition($this);
                $expenses_parent_conditions = Expenses::parentToCondition($this);

                $trashed_by_id = $by instanceof User ? $by->getId() : AngieApplication::authentication()->getLoggedUserId();

                DB::execute("UPDATE time_records SET original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $time_parent_conditions AND is_trashed = ?", true, true);
                DB::execute("UPDATE time_records SET is_trashed = ?, trashed_on = ?, trashed_by_id = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $time_parent_conditions AND is_trashed = ?", true, DateTimeValue::now(), $trashed_by_id, false, false);

                DB::execute("UPDATE expenses SET original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $expenses_parent_conditions AND is_trashed = ?", true, true);
                DB::execute("UPDATE expenses SET is_trashed = ?, trashed_on = ?, trashed_by_id = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $expenses_parent_conditions AND is_trashed = ?", true, DateTimeValue::now(), $trashed_by_id, false, false);

                TimeRecords::clearCache();
                Expenses::clearCache();
            }
        );

        $this->registerEventHandler(
            'on_before_restore_from_trash',
            function ($bulk) {
                $time_parent_conditions = TimeRecords::parentToCondition($this);
                $expenses_parent_conditions = Expenses::parentToCondition($this);

                DB::execute("UPDATE time_records SET is_trashed = ?, trashed_on = NULL, trashed_by_id = ?, updated_on = UTC_TIMESTAMP() WHERE $time_parent_conditions AND original_is_trashed = ?", false, 0, false);
                DB::execute("UPDATE time_records SET is_trashed = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $time_parent_conditions AND is_trashed = ?", true, false, true);

                DB::execute("UPDATE expenses SET is_trashed = ?, trashed_on = NULL, trashed_by_id = ?, updated_on = UTC_TIMESTAMP() WHERE $expenses_parent_conditions AND original_is_trashed = ?", false, 0, false);
                DB::execute("UPDATE expenses SET is_trashed = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE $expenses_parent_conditions AND is_trashed = ?", true, false, true);

                TimeRecords::clearCache();
                Expenses::clearCache();
            }
        );

        $this->registerEventHandler(
            'on_before_delete',
            function () {
                $time_record_ids = DB::execute('SELECT id FROM time_records WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId());
                $expense_ids = DB::execute('SELECT id FROM expenses WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId());

                if ($time_record_ids || $expense_ids) {
                    try {
                        DB::beginWork('Droping time and expense records @ ' . __CLASS__);

                        $parent_ids_map = [];

                        if ($time_record_ids) {
                            DB::execute('DELETE FROM time_records WHERE id IN (?)', $time_record_ids);
                            $parent_ids_map['TimeRecord'] = $time_record_ids;
                        }
                        if ($expense_ids) {
                            DB::execute('DELETE FROM expenses WHERE id IN (?)', $expense_ids);
                            $parent_ids_map['Expense'] = $expense_ids;
                        }

                        ActivityLogs::deleteByParents($parent_ids_map);
                        ModificationLogs::deleteByParents($parent_ids_map);

                        DB::commit('Time and expense records dropped @ ' . __CLASS__);
                    } catch (Exception $e) {
                        DB::rollback('Failed to drop time and expense records @ ' . __CLASS__);
                        throw $e;
                    }

                    TimeRecords::clearCache();
                    Expenses::clearCache();
                }
            }
        );
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return parent object ID.
     *
     * @return int
     */
    abstract public function getId();

    /**
     * Return default billable status for this object type.
     *
     * @return int
     */
    public function getDefaultBillableStatus()
    {
        return AngieApplication::cache()->getByObject($this, ['default_billable_status'], function () {
            if ($this instanceof Project) {
                return ConfigOptions::getValueFor('default_billable_status', $this) ? 1 : 0;
            } elseif ($this instanceof Task && $this->getProject() instanceof Project) {
                return ConfigOptions::getValueFor('default_billable_status', $this->getProject()) ? 1 : 0;
            } else {
                return ConfigOptions::getValue('default_billable_status') ? 1 : 0;
            }
        });
    }

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
    public function trackTime($value, IUser $user, JobType $job_type, DateValue $date, $billable_status = TimeRecord::BILLABLE, IUser $by = null)
    {
        if ($by instanceof IUser) {
            $created_by = $by;
        } else {
            $created_by = $user;
        }

        return TimeRecords::create(
            [
                'parent_type' => get_class($this),
                'parent_id' => $this->getId(),
                'job_type_id' => $job_type->getId(),
                'record_date' => $date,
                'value' => $value,
                'user_id' => $user->getId(),
                'user_name' => $user->getDisplayName(),
                'user_email' => $user->getEmail(),
                'billable_status' => $billable_status,
                'created_by_id' => $created_by->getId(),
                'created_by_name' => $created_by->getDisplayName(),
                'created_by_email' => $created_by->getEmail(),
            ]
        );
    }

    /**
     * Returns time records attached to parent object.
     *
     * Optional filter is billable status (or array of statuses)
     *
     * @param  mixed    $billable_status
     * @return DBresult
     */
    public function getTimeRecords(User $user, $billable_status = null)
    {
        return TimeRecords::findByParent($this, $billable_status);
    }

    public function getTimeRecordsCollection(
        User $user,
        int $page = 1,
        ?DateValue $from = null,
        ?DateValue $to = null
    ): TimeRecordsByParentCollection
    {
        return TimeRecords::prepareCollection(
            sprintf(
                'time_records_by_parent_%s:%s_page_%s_%s:%s',
                get_class($this),
                $this->getId(),
                $page,
                $from,
                $to
            ),
            $user
        );
    }

    public function getTimeRecordsInfo(User $user, ?DateValue $from = null, ?DateValue $to = null): array
    {
        return TimeRecords::sumByUserAndParent($user, $this, $from, $to);
    }

    public function getExpensesInfo(User $user, ?DateValue $from = null, ?DateValue $to = null): array
    {
        return Expenses::sumByUserAndParent($user, $this, $from, $to);
    }

    /**
     * Log time and return time record.
     *
     * @param  float   $value
     * @param  int     $billable_status
     * @return Expense
     */
    public function trackExpense($value, IUser $user, ExpenseCategory $category, DateValue $date, $billable_status = Expense::BILLABLE, IUser $by = null)
    {
        if ($by instanceof IUser) {
            $created_by = $by;
        } else {
            $created_by = $user;
        }

        return Expenses::create([
            'parent_type' => get_class($this),
            'parent_id' => $this->getId(),
            'category_id' => $category->getId(),
            'record_date' => $date,
            'value' => $value,
            'user_id' => $user->getId(),
            'billable_status' => $billable_status,
            'created_by_id' => $created_by->getId(),
            'created_by_name' => $created_by->getDisplayName(),
            'created_by_email' => $created_by->getEmail(),
        ]);
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns tracked expenses attached to the parent parent object.
     *
     * Optional filter is billable status (or array of statuses)
     *
     * @param  mixed    $billable_status
     * @return DBResult
     */
    public function getExpenses(User $user, $billable_status = null)
    {
        return Expenses::findByParent($this, $billable_status);
    }

    /**
     * Rebuild tracking updates.
     */
    public function rebuildTrackingUpdates()
    {
        if ($tracking_objects = TrackingObjects::findByParent($this)) {
            foreach ($tracking_objects as $object) {
                DB::execute('UPDATE activity_logs SET parent_path = ? WHERE parent_type = ? AND parent_id = ?', $object->getObjectPath(), get_class($object), $object->getId());
            }
        }
    }

    /**
     * Return true if $user can track time for this object.
     *
     * @return bool
     */
    public function canTrackTime(User $user)
    {
        return $this->canTrackTimeAndExpenses($user);
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return true if $user can track time and expenses for this object.
     *
     * @return bool
     */
    private function canTrackTimeAndExpenses(User $user)
    {
        if ($user instanceof Client) {
            return false;
        }

        $project = $this instanceof Project ? $this : $this->getProject();

        if ($project instanceof Project && $project->getIsTrackingEnabled()) {
            if ($user->isOwner() || $project->isLeader($user)) {
                return true;
            }

            return $project->isMember($user);
        }

        return false;
    }

    /**
     * Return true if $user can track expenses for this object.
     *
     * @return bool
     */
    public function canTrackExpenses(User $user)
    {
        return $this->canTrackTimeAndExpenses($user);
    }
}
