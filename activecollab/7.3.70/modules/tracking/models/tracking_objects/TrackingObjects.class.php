<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Tracking objects manager.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
final class TrackingObjects
{
    /**
     * Returns true if $user can track time and expanses in $project.
     *
     * @return bool
     */
    public static function canAdd(IUser $user, Project $project)
    {
        return $user instanceof User && $project->getIsTrackingEnabled() && $project->isMember($user);
    }

    /**
     * Returns true if $user can manage time and expenses in $project.
     *
     * @return bool
     */
    public static function canManage(IUser $user, Project $project)
    {
        return $user instanceof User && ($user->isPowerUser() || $project->isLeader($user));
    }

    // ---------------------------------------------------
    //  Finders
    // ---------------------------------------------------
    /**
     * Return tracking objects by parent.
     *
     * @return array
     */
    public static function findByParent(ITracking $parent)
    {
        $parent_type = DB::escape(get_class($parent));
        $parent_id = DB::escape($parent->getId());

        $rows = DB::execute("(SELECT 'TimeRecord' AS 'type', time_records.id, time_records.parent_type, time_records.parent_id, time_records.job_type_id AS 'type_id', time_records.is_trashed, time_records.original_is_trashed, time_records.trashed_on, time_records.record_date, time_records.value, time_records.user_id, time_records.user_name, time_records.user_email, time_records.summary, time_records.billable_status, time_records.created_on, time_records.created_by_id, time_records.created_by_name, time_records.created_by_email FROM time_records WHERE parent_type = $parent_type AND parent_id = $parent_id AND is_trashed = ?) UNION ALL
                           (SELECT 'Expense' AS 'type', expenses.id, expenses.parent_type, expenses.parent_id, expenses.category_id AS type_id, expenses.is_trashed, expenses.original_is_trashed, expenses.trashed_on, expenses.record_date, expenses.value, expenses.user_id, expenses.user_name, expenses.user_email, expenses.summary, expenses.billable_status, expenses.created_on, expenses.created_by_id, expenses.created_by_name, expenses.created_by_email FROM expenses WHERE parent_type = $parent_type AND parent_id = $parent_id AND is_trashed = ?) ORDER BY record_date DESC, created_on DESC", false, false);

        if ($rows) {
            $result = [];

            foreach ($rows as $row) {
                if ($row['type'] == 'TimeRecord') {
                    $item = new TimeRecord();
                    $row['job_type_id'] = $row['type_id'];
                } else {
                    $item = new Expense();
                    $row['category_id'] = $row['type_id'];
                }

                unset($row['type_id']);

                $item->loadFromRow($row);

                $result[] = $item;
            }

            return $result;
        }

        return null;
    }

    /**
     * Return amount of money spent on given project.
     *
     * Cost is calculated based on job types and expenses
     *
     * @return float
     */
    public static function sumCostByProject(Project $project, User $user)
    {
        $billable = DB::escape(ITrackingObject::BILLABLE);

        $parent_conditions = self::prepareParentTypeFilter($project, $user);

        $rows = DB::execute("(SELECT time_records.value AS 'value', time_records.job_type_id AS 'job_type_id', 'TimeRecord' AS 'type' FROM time_records WHERE $parent_conditions AND is_trashed = ? AND billable_status >= $billable) UNION ALL
                           (SELECT expenses.value AS 'value', '0' AS 'job_type_id', 'Expense' AS 'type' FROM expenses WHERE $parent_conditions AND is_trashed = ? AND billable_status >= $billable)", false, false);

        if ($rows) {
            $job_types = JobTypes::getIdRateMapFor($project);

            $result = 0;

            foreach ($rows as $row) {
                if ($row['type'] == 'TimeRecord') {
                    $job_type_id = (int) $row['job_type_id'];

                    if (isset($job_types[$job_type_id])) {
                        $result += (float) $row['value'] * $job_types[$job_type_id];
                    }
                } else {
                    $result += $row['value'];
                }
            }

            return $result;
        } else {
            return 0;
        }
    }

    /**
     * Prepare parent type filter.
     *
     * @return string
     */
    public static function prepareParentTypeFilter(Project $parent, User $user)
    {
        $types = ['Project' => [$parent->getId()]];

        if ($user instanceof Client) {
            $task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ? AND is_hidden_from_clients = ? AND is_trashed = ?', $parent->getId(), false, false);
        } else {
            $task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ? AND is_trashed = ?', $parent->getId(), false);
        }

        if ($task_ids) {
            $types['Task'] = $task_ids;
        }

        $conditions = [];

        foreach ($types as $type_name => $object_ids) {
            $conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $type_name, array_unique($object_ids));
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Return a list of project ID-s based on a list of time record and expense ID-s.
     *
     * Note: This method was extracted from Invoice class (it is used to fetch a list of related projects). Reason why
     * it was extracted is to have it with other methods that work with tracking objects in agregate.
     *
     * @param $time_record_ids
     * @param $expense_ids
     * @return array
     * @throws InvalidParamError
     */
    public static function getProjectIdsFromTrackingObjectIds($time_record_ids, $expense_ids)
    {
        $project_ids = [];
        $task_ids = [];

        foreach (['expenses' => $expense_ids, 'time_records' => $time_record_ids] as $table_name => $ids) {
            if (!empty($ids)) {
                if ($table_project_ids = DB::executeFirstColumn("SELECT DISTINCT parent_id FROM $table_name WHERE parent_type = ? AND id IN (?)", Project::class, $ids)) {
                    $project_ids = array_merge($project_ids, $table_project_ids);
                }

                if ($table_task_ids = DB::executeFirstColumn("SELECT DISTINCT parent_id FROM $table_name WHERE parent_type = ? AND id IN (?)", Task::class, $ids)) {
                    $task_ids = array_merge($task_ids, $table_task_ids);
                }
            }
        }

        if (!empty($task_ids)) {
            if ($task_project_ids = DB::executeFirstColumn('SELECT DISTINCT project_id FROM tasks WHERE id IN (?)', $task_ids)) {
                $project_ids = array_merge($project_ids, $task_project_ids);
            }
        }

        return array_unique($project_ids);
    }
}
