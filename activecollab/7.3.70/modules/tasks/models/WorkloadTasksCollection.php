<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class WorkloadTasksCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var string|bool
     */
    private $tag = false;

    /**
     * @var string|bool
     */
    private $timestamp_hash = false;

    /**
     * @var array|bool
     */
    private $tasks_collection = false;
    private $task_ids = false;

    /**
     * @var string|bool
     */
    private $filter = false;

    /**
     * @var DateValue|bool
     */
    private $start_date;
    private $end_date = false;

    public function &setFilter(string $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function &setStartDate(DateValue $start_date): self
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function &setEndDate(DateValue $end_date): self
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function execute()
    {
        $tasks = $this->getTasksCollection();

        if (empty($tasks)) {
            return [];
        }

        $is_power_user = $this->getWhosAsking()->isPowerUser(true);
        $project_ids = $is_power_user ? (array) $this->getWhosAsking()->getProjectIds() : [];

        $result = [];

        foreach ($tasks as $task) {
            $project_id = (int) $task['project_id'];

            $item = [
                'id' => (int) $task['id'],
                'assignee_id' => (int) $task['assignee_id'],
                'start_on' => new DateValue($task['start_on']),
                'due_on' => new DateValue($task['due_on']),
                'estimate' => $task['estimate'],
                'is_completed' => $task['completed_on'] !== null,
            ];

            if (!$is_power_user || ($is_power_user && in_array($project_id, $project_ids))) {
                $item['name'] = $task['name'];
                $item['project_id'] = $project_id;
            } else {
                $item['name'] = null;
                $item['project_id'] = null;
            }

            $result[] = $item;
        }

        return $result;
    }

    public function count()
    {
        if ($task_ids = $this->getTaskIds()) {
            return count($task_ids);
        }

        return 0;
    }

    public function getModelName(): string
    {
        return Tasks::class;
    }

    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false || empty($use_cache)) {
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    private function getTimestampHash()
    {
        if ($this->timestamp_hash === false) {
            $this->timestamp_hash = sha1(
                $this->getTasksTimestampHash() . '-' .
                $this->getProjectsTimestampHash()
            );
        }

        return $this->timestamp_hash;
    }

    private function getTasksTimestampHash()
    {
        if ($this->count() > 0) {
            return sha1(
                DB::executeFirstCell(
                    "SELECT GROUP_CONCAT(updated_on ORDER BY id SEPARATOR ',') AS 'timestamp_hash' FROM tasks WHERE id IN (?)",
                    $this->getTaskIds()
                )
            );
        }

        return sha1($this->getModelName());
    }

    private function getProjectsTimestampHash()
    {
        if ($this->count() > 0) {
            return sha1(
                DB::executeFirstCell(
                    "SELECT GROUP_CONCAT(p.updated_on ORDER BY p.id SEPARATOR ',') AS 'timestamp_hash'
                      FROM tasks t 
                       LEFT JOIN projects p ON t.project_id = p.id
                         WHERE t.id IN (?)",
                    $this->getTaskIds()
                )
            );
        }

        return sha1($this->getModelName());
    }

    private function getTaskIds(): ?array
    {
        if ($this->task_ids === false) {
            if (!$this->getWhosAsking()->isPowerUser()) {
                throw new RuntimeException('Workload collection is not available for this user role.');
            }

            $conditions = [
                DB::prepare(
                    't.start_on IS NOT NULL AND t.due_on IS NOT NULL AND t.is_trashed = ?',
                    false
                ),
            ];

            if ($this->filter === 'assignee') {
                $conditions[] = DB::prepare('t.assignee_id IN (?)', $this->getWhosAsking()->getVisibleUserIds());
            } elseif ($this->filter === 'unassignee') {
                $conditions[] = DB::prepare('t.assignee_id = 0');
            }

            $conditions[] = DB::prepare(
                '(t.start_on BETWEEN ? AND ? OR t.due_on BETWEEN ? AND ?) OR (t.start_on < ? AND t.due_on > ?)',
                $this->start_date,
                $this->end_date,
                $this->start_date,
                $this->end_date,
                $this->start_date,
                $this->end_date
            );

            $this->task_ids = DB::executeFirstColumn(
                sprintf(
                    'SELECT t.id FROM tasks t
                      LEFT JOIN projects p ON p.id = t.project_id
                       WHERE p.is_sample = ? AND %s',
                    implode(' AND ', $conditions)
                ),
                false
            );
        }

        return $this->task_ids;
    }

    private function getTasksCollection(): ?DBResult
    {
        if ($this->tasks_collection === false) {
            $this->tasks_collection = DB::execute(
                'SELECT t.id, t.name, t.project_id, t.assignee_id, t.start_on, t.due_on, t.task_list_id, t.estimate, t.completed_on
                  FROM tasks t
                   LEFT JOIN task_lists tl ON tl.id = t.task_list_id
                    WHERE t.id IN (?)
                     ORDER BY tl.position ASC, t.position ASC',
                $this->getTaskIds()
            );

            $this->tasks_collection = $this->tasks_collection ?? null;
        }

        return $this->tasks_collection;
    }
}
