<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\TimeRecordEvents\TimeRecordCreatedEvent;
use ActiveCollab\Module\Tracking\Events\DataObjectLifeCycleEvents\TimeRecordEvents\TimeRecordUpdatedEvent;
use ActiveCollab\Module\Tracking\Services\TrackingServiceInterface;

/**
 * Time records manager class.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
class TimeRecords extends BaseTimeRecords
{
    use ITrackingObjectsImplementation;

    /**
     * Return new collection.
     *
     * @param  User|null         $user
     * @return ModelCollection
     * @throws InvalidParamError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        if (str_starts_with($collection_name, 'time_records_in_project') || str_starts_with($collection_name, 'filtered_time_records_in_project')) {
            return (new ProjectTimeRecordsCollection($collection_name))->setWhosAsking($user);
        } else {
            if (str_starts_with($collection_name, 'time_records_in_task')) {
                return (new TaskTimeRecordsCollection($collection_name))->setWhosAsking($user);
            } else {
                if (str_starts_with($collection_name, 'time_records_by_user') || str_starts_with($collection_name, 'filtered_time_records_by_user')) {
                    return (new UserTimeRecordsCollection($collection_name))->setWhosAsking($user);
                } else {
                    if (str_starts_with($collection_name, 'timesheet_report')) {
                        return (new TimesheetReportCollection($collection_name))->setWhosAsking($user);
                    } elseif (str_starts_with($collection_name, 'user_timesheet_report')) {
                        return (new UserTimesheetReportCollection($collection_name))->setWhosAsking($user);
                    } elseif (str_starts_with($collection_name, 'time_records_by_parent')) {
                        return (new TimeRecordsByParentCollection($collection_name))->setWhosAsking($user);
                    } else {
                        throw new InvalidParamError('collection_name', $collection_name);
                    }
                }
            }
        }
    }

    /**
     * Return time records by parent.
     *
     * @param  int      $billable_status
     * @return DBResult
     */
    public static function findByParent(ITracking $parent, $billable_status = null)
    {
        if ($billable_status) {
            return self::find([
                'conditions' => ['parent_type = ? AND parent_id = ? AND billable_status = ? AND is_trashed = ?', get_class($parent), $parent->getId(), $billable_status, false],
            ]);
        } else {
            return self::find([
                'conditions' => ['parent_type = ? AND parent_id = ? AND is_trashed = ?', get_class($parent), $parent->getId(), false],
            ]);
        }
    }

    /**
     * Sum time by task.
     *
     * @return float
     */
    public static function sumByTask(Task $task)
    {
        $time_value = 0;

        if ($time_records = DB::execute('SELECT value FROM time_records WHERE ' . self::parentToCondition($task) . ' AND is_trashed = ?', false)) {
            foreach ($time_records as $time_record) {
                $time_value += time_to_minutes($time_record['value']);
            }
        }

        return minutes_to_time($time_value);
    }

    public static function sumByUserAndParent(
        User $user,
        ITracking $parent,
        ?DateValue $from = null,
        ?DateValue $to = null
    ): array
    {
        $your_time = 0;
        $other_time = 0;

        $conditions = [self::parentToCondition($parent)];
        $conditions[] = DB::prepare('(is_trashed = ?)', false);

        if ($from && $to) {
            $conditions[] = DB::prepare('(record_date BETWEEN ? AND ?)', $from, $to);
        } elseif ($from && !$to) {
            $conditions[] = DB::prepare('(record_date >= ?)', $from);
        } elseif (!$from && $to) {
            $conditions[] = DB::prepare('(record_date <= ?)', $to);
        }

        if ($time_records = DB::execute('SELECT user_id, value FROM time_records WHERE ' . implode(' AND ', $conditions))) {
            foreach ($time_records as $time_record) {
                if ($user->getId() === (int) $time_record['user_id']) {
                    $your_time += time_to_minutes($time_record['value']);
                } else {
                    $other_time += time_to_minutes($time_record['value']);
                }
            }
        }

        return [
            'your_time' => minutes_to_time($your_time),
            'other_time' => minutes_to_time($other_time),
        ];
    }

    /**
     * Get sum of all time records tracked directly on the project.
     */
    public static function sumDirectlyOnProject(int $projectId): float
    {
        $time_value = 0;

        if ($time_records = DB::execute('SELECT value FROM time_records WHERE parent_type = "Project" AND parent_id = ? AND is_trashed = ?', $projectId, false)) {
            foreach ($time_records as $time_record) {
                $time_value += time_to_minutes($time_record['value']);
            }
        }

        return minutes_to_time($time_value);
    }

    public static function getUsedJobTypeIds(int $projectId): array
    {
        $query = '
            SELECT DISTINCT job_type_id
            FROM time_records
            WHERE
             is_trashed = 0
             AND billable_status >= ?
             AND ((parent_type = "Project" AND parent_id = ?) OR (parent_type = "Task" AND parent_id IN (SELECT id FROM tasks WHERE project_id = ?)))';

        return DB::executeFirstColumn($query, TimeRecord::BILLABLE, $projectId, $projectId) ?? [];
    }

    /**
     * Find time records by task list.
     *
     * @param  int|int[] $statuses
     * @return array
     */
    public static function findByTaskList(TaskList $task_list, $statuses)
    {
        if ($task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE task_list_id = ? AND project_id = ? AND is_trashed = ?', $task_list->getId(), $task_list->getProjectId(), false)) {
            return self::find([
                'conditions' => ['parent_type = ? AND parent_id IN (?) AND billable_status IN (?) AND is_trashed = ?', 'Task', $task_ids, $statuses, false],
            ]);
        }

        return null;
    }

    /**
     * Group records by job type.
     *
     * @param  TimeRecord[] $records
     * @return array
     */
    public static function groupByJobType($records)
    {
        $grouped = [];

        if (is_foreachable($records)) {
            foreach ($records as $time_record) {
                $key = $time_record->getJobTypeName();

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [];
                }

                $grouped[$key][] = $time_record;
            }
        }

        return $grouped;
    }

    /**
     * Check if all time records have the same job hourly rate.
     *
     * @param  TimeRecord[] $records
     * @return mixed        unit_cost or false
     */
    public static function isIdenticalJobRate($records)
    {
        if (is_foreachable($records)) {
            $previous = null;

            foreach ($records as $time_record) {
                $job_type_id = $time_record->getJobTypeId();
                $project = $time_record->getProject();

                $job_type_rates = JobTypes::getIdRateMapFor($project); //job_type_id => cost

                $job_type_rate = isset($job_type_rates[$job_type_id]) ? $job_type_rates[$job_type_id] : 0;

                if ($previous !== null && $job_type_rate != $previous) {
                    return false;
                }

                $previous = $job_type_rate;
            }

            return $previous;
        }

        return true;
    }

    /**
     * Return number of time records that use this particular job type.
     *
     * @return int
     */
    public static function countByJobType(JobType $job_type)
    {
        return self::count(['job_type_id = ?', $job_type->getId()]);
    }

    /**
     * Change billable status by IDs.
     *
     * @param  int[]    $ids
     * @param  int      $new_status
     * @return DbResult
     */
    public static function changeBilableStatusByIds($ids, $new_status)
    {
        return DB::execute('UPDATE time_records SET billable_status = ? WHERE id IN (?)', $new_status, $ids);
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): TimeRecord
    {
        $time_record = parent::create($attributes, false, false);

        $tracking_service = AngieApplication::getContainer()
            ->get(TrackingServiceInterface::class);

        $internal_rate = $tracking_service->getInternalRateForTimeRecord($time_record);
        $job_type_rate = $tracking_service->getJobTypeRateForTimeRecord($time_record);

        $time_record->setInternalRate($internal_rate);
        $time_record->setJobTypeHourlyRate($job_type_rate);

        if ($save){
            $time_record->save();
            DataObjectPool::introduce($time_record);
        }

        if ($announce) {
            AngieApplication::eventsDispatcher()->trigger(new TimeRecordCreatedEvent($time_record));
        }

        return $time_record;
    }

    public static function canAccessUsersTimeRecords(User $whos_asking, User $for_user): bool
    {
        if (!$for_user->isLoaded()) {
            return false;
        }

        if ($for_user->isClient()) {
            return false;
        }

        return $whos_asking->is($for_user) || $whos_asking->isOwner();
    }

    public static function preloadDetailsByIds(array $time_records_ids)
    {
        DataObjectPool::getByIds(TimeRecord::class, $time_records_ids);
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): TimeRecord
    {
        $time_record = parent::update($instance, $attributes, false);

        $tracking_service = AngieApplication::getContainer()
            ->get(TrackingServiceInterface::class);

        $internal_rate = $tracking_service->getInternalRateForTimeRecord($time_record);
        $job_type_rate = $tracking_service->getJobTypeRateForTimeRecord($time_record);

        $time_record->setInternalRate($internal_rate);
        $time_record->setJobTypeHourlyRate($job_type_rate);

        if ($save){
            $time_record->save();
        }

        AngieApplication::eventsDispatcher()->trigger(new TimeRecordUpdatedEvent($time_record));

        return $time_record;
    }
}
