<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class TimesheetReportCollection extends TimeRecordsCollection
{
    /**
     * @var DateValue
     */
    protected $from_date;

    /**
     * @var DateValue
     */
    protected $to_date;

    /**
     * @var string|bool
     */
    protected $query_conditions = false;

    protected $bits = [];

    public function __construct($name)
    {
        parent::__construct($name);

        $this->bits = explode('_', $name);

        [$this->from_date, $this->to_date] = $this->prepareFromToFromCollectionName($this->bits);
    }

    protected function getQueryConditions()
    {
        if ($this->query_conditions === false) {
            if (!$this->getWhosAsking()->isPowerUser()) {
                throw new RuntimeException('Collection can be prepared for power user only.');
            }

            $project_ids = DB::executeFirstColumn(
                'SELECT id FROM projects WHERE is_trashed = ? AND is_sample = ? AND is_tracking_enabled = ?',
                false,
                false,
                true
            );

            if ($project_ids) {
                $conditions = [
                    DB::prepare(
                        '(is_trashed = ? AND (record_date BETWEEN ? AND ?)',
                        false,
                        $this->from_date,
                        $this->to_date
                    ),
                ]; // untrashed records for date range

                $project_ids = DB::escape($project_ids);
                $task_subquery = DB::prepare(
                    "SELECT id FROM tasks WHERE project_id IN ($project_ids) AND is_trashed = ?",
                    false
                );

                $conditions[] = DB::prepare("((parent_type = 'Project' AND parent_id IN ($project_ids)) OR (parent_type = 'Task' AND parent_id IN ($task_subquery))))");
            } else {
                throw new ImpossibleCollectionError();
            }

            $this->query_conditions = implode(' AND ', $conditions);
        }

        return $this->query_conditions;
    }

    protected function getRelatedFromTimeRecords($time_records)
    {
        $visible_project_ids = [];
        $visible_task_ids = [];
        $is_owner = $this->getWhosAsking()->isOwner();

        if (!$is_owner) {
            $visible_project_ids = Projects::findIdsByUser(
                $this->getWhosAsking(),
                false,
                [
                    'is_trashed = ? AND is_sample = ? AND is_tracking_enabled = ?',
                    false,
                    false,
                    true,
                ]
            ) ?? [];
            $visible_task_ids = is_array($visible_project_ids) && count($visible_project_ids) > 0
                ? DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id IN (?) AND is_trashed = ?', $visible_project_ids, false)
                : [];
        }

        $type_ids_map = ['Project' => [], 'Task' => []];

        /** @var TimeRecord[] $time_records */
        if ($time_records) {
            foreach ($time_records as $time_record) {
                if ($time_record->getParentType() == 'Project' && !in_array($time_record->getParentId(), $type_ids_map['Project'])) {
                    if ($is_owner || (!$is_owner && in_array($time_record->getParentId(), $visible_project_ids))) {
                        $type_ids_map['Project'][] = $time_record->getParentId();
                    }
                } else {
                    if ($time_record->getParentType() == 'Task' && !in_array($time_record->getParentId(), $type_ids_map['Task'])) {
                        if ($is_owner || (!$is_owner && in_array($time_record->getParentId(), $visible_task_ids))) {
                            $type_ids_map['Task'][] = $time_record->getParentId();
                        }
                    }
                }
            }
        }

        if (empty($type_ids_map['Task'])) {
            unset($type_ids_map['Task']);
        } else {
            if ($project_ids = DB::executeFirstColumn('SELECT DISTINCT project_id FROM tasks WHERE id IN (?) GROUP BY project_id, id', $type_ids_map['Task'])) {
                foreach ($project_ids as $project_id) {
                    if (!in_array($project_id, $type_ids_map['Project'])) {
                        $type_ids_map['Project'][] = $project_id;
                    }
                }
            }
        }

        if (empty($type_ids_map['Project'])) {
            unset($type_ids_map['Project']);
        }

        $this->preload($type_ids_map);

        $result = DataObjectPool::getByTypeIdsMap($type_ids_map);

        if (empty($result)) {
            $result = [];
        }

        return $result;
    }
}
