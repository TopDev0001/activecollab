<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Base time records collection.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
abstract class TimeRecordsCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    public function getModelName(): string
    {
        return 'TimeRecords';
    }

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    /**
     * @return string
     */
    protected function getTimestampHash()
    {
        if ($this->getCurrentPage() && $this->getItemsPerPage()) {
            $limit = ' LIMIT ' . (($this->getCurrentPage() - 1) * $this->getItemsPerPage()) . ', ' . $this->getItemsPerPage();
        } else {
            $limit = '';
        }

        return sha1($this->getProjectsTimestamp() . DB::executeFirstCell("SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM time_records WHERE " . $this->getQueryConditions() . ' ORDER BY ' . $this->getOrderBy() . $limit));
    }

    /**
     * @return string
     */
    private function getProjectsTimestamp()
    {
        return DB::executeFirstCell('SELECT MAX(updated_on) FROM projects');
    }

    /**
     * Prepare query conditions.
     *
     * @return string
     * @throws ImpossibleCollectionError
     */
    abstract protected function getQueryConditions();

    /**
     * Return how time records should be ordered.
     *
     * @return string
     */
    protected function getOrderBy()
    {
        return 'record_date DESC, id DESC';
    }

    /**
     * Use this in child collection class if you don't need releated objects.
     * By default, releated objects are included.
     */
    protected bool $include_releated = true;

    /**
     * @return array
     */
    public function execute()
    {
        try {
            $time_records = $this->queryTimeRecords();
        } catch (ImpossibleCollectionError $e) {
            $time_records = null;
        }

        if (empty($time_records)) {
            $time_records = [];
        }

        if (!$this->include_releated) {
            return $time_records;
        }

        return [
            'time_records' => $time_records,
            'related' => $this->getRelatedFromTimeRecords($time_records),
        ];
    }

    /**
     * @return TimeRecord[]|DbResult
     */
    protected function queryTimeRecords()
    {
        if ($this->getCurrentPage() && $this->getItemsPerPage()) {
            $offset = ($this->getCurrentPage() - 1) * $this->getItemsPerPage();
            $limit = $this->getItemsPerPage();
        } else {
            $offset = null;
            $limit = null;
        }

        return TimeRecords::find([
            'conditions' => $this->getQueryConditions(),
            'order' => $this->getOrderBy(),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Load related projects and times based on a list of time records.
     *
     * @param  TimeRecord[] $time_records
     * @return array
     */
    protected function getRelatedFromTimeRecords($time_records)
    {
        $type_ids_map = ['Project' => [], 'Task' => []];

        /** @var TimeRecord[] $time_records */
        if ($time_records) {
            foreach ($time_records as $time_record) {
                if ($time_record->getParentType() == 'Project' && !in_array($time_record->getParentId(), $type_ids_map['Project'])) {
                    $type_ids_map['Project'][] = $time_record->getParentId();
                } else {
                    if ($time_record->getParentType() == 'Task' && !in_array($time_record->getParentId(), $type_ids_map['Task'])) {
                        $type_ids_map['Task'][] = $time_record->getParentId();
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

    protected function preload(array $type_ids_map)
    {
        // preload projects counts
        if (isset($type_ids_map[Project::class])) {
            Projects::preloadProjectElementCounts($type_ids_map[Project::class]);
        }

        // preload tasks counts
        if (isset($type_ids_map[Task::class])) {
            $task_ids = $type_ids_map[Task::class];

            Subtasks::preloadCountByTasks($task_ids);
            TaskDependencies::preloadCountByTasks($task_ids);
            Attachments::preloadDetailsByParents(Task::class, $task_ids);
            Comments::preloadCountByParents(Task::class, $task_ids);
            Labels::preloadDetailsByParents(Task::class, $task_ids);
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        try {
            return TimeRecords::count($this->getQueryConditions());
        } catch (ImpossibleCollectionError $e) {
            return 0;
        }
    }

    /**
     * Prepare pagination from bits.
     *
     * @param  int               $items_per_page
     * @throws InvalidParamError
     */
    protected function preparePaginationFromCollectionName(array &$bits, $items_per_page = 100)
    {
        $page = array_pop($bits);

        if (is_numeric($page)) {
            $page = (int) $page;
        } else {
            throw new InvalidParamError('bits', $bits, 'Page expected');
        }

        $separator = array_pop($bits);

        if ($separator === 'page') {
            $this->setPagination($page, $items_per_page);
        } else {
            throw new InvalidParamError('bits', $bits, '_page_ separator expected');
        }
    }

    /**
     * Prepare and return ID from name $bits array.
     *
     * @return int
     * @throws InvalidParamError
     */
    protected function prepareIdFromCollectionName(array &$bits)
    {
        $id = array_pop($bits);

        if (is_numeric($id)) {
            return (int) $id;
        } else {
            throw new InvalidParamError('bits', $bits, 'Expected ID as last bit');
        }
    }

    /**
     * Get from and to DateValue instances from collection name bits.
     *
     * @return DateValue[]
     * @throws InvalidParamError
     */
    protected function prepareFromToFromCollectionName(array &$bits)
    {
        $from_to_string = array_pop($bits);

        if (strpos($from_to_string, ':') === false) {
            throw new InvalidParamError('bits', $bits, 'Expected from:to bit');
        } else {
            [$from, $to] = explode(':', $from_to_string);

            $from = $from ? DateValue::makeFromString($from) : null;
            $to = $to ? DateValue::makeFromString($to) : null;

            if (empty($from) || empty($to)) {
                throw new InvalidParamError('bits', $bits, 'from:to bit is not valid (we got empty values)');
            }
        }

        return [$from, $to];
    }

    /**
     * Members and subcontractors can see only their time records.
     */
    protected function filterTimeRecordsByUserRole(User $user, Project $project, array &$conditions)
    {
        if (!($user instanceof Client || $user->isOwner() || $project->isLeader($user))) {
            $conditions[] = DB::prepare('(user_id = ?)', $user->getId()); // Just for memebers and subcontractors
        }
    }
}
