<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class ProjectTasksRawCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var Project
     */
    private $project;

    private string $task_view;

    private string $task_status;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * @var string
     */
    private $timestamp_hash = false;

    /**
     * @var array|bool
     */
    private $tasks_collection = false;

    /**
     * @var array|bool
     */
    private $task_ids = false;

    /**
     * @var bool|string
     */
    private $conditions = false;

    public function &setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function &setTaskView(string $task_view): self
    {
        $this->task_view = $task_view;

        return $this;
    }

    public function &setTaskStatus(string $task_status): self
    {
        $this->task_status = $task_status;

        return $this;
    }

    private function getCommonConditions(): array
    {
        $conditions = [
            DB::prepare(
                'project_id = ? AND is_trashed = ?',
                $this->project->getId(),
                false
            ),
        ];

        if ($this->getWhosAsking()->isClient()) {
            $conditions[] = DB::prepare('is_hidden_from_clients = ?', false);
        }

        return $conditions;
    }

    private function getCompletedTaskIds(bool $only_open_task_lists = false, int $limit = 0): array
    {
        $conditions = array_merge(
            $this->getCommonConditions(),
            [DB::prepare('completed_on IS NOT NULL')]
        );

        if ($only_open_task_lists) {
            $conditions[] = DB::prepare(
                '(task_list_id IN (SELECT id FROM task_lists WHERE project_id = ? AND is_trashed = ? AND completed_on IS NULL))',
                $this->project->getId(),
                false
            );
        }

        $format = $limit > 0
            ? "SELECT id FROM tasks WHERE %s ORDER BY completed_on DESC LIMIT {$limit}"
            : 'SELECT id FROM tasks WHERE %s ORDER BY completed_on DESC LIMIT 2500';

        $ids = DB::executeFirstColumn(
            sprintf(
                $format,
                implode(' AND ', $conditions)
            )
        );

        return $ids ?? [];
    }

    private function getOpenTaskIds(): array
    {
        $conditions = array_merge(
            $this->getCommonConditions(),
            [DB::prepare('completed_on IS NULL')]
        );

        $ids = DB::executeFirstColumn(
            sprintf(
                'SELECT id FROM tasks WHERE %s',
                implode(' AND ', $conditions)
            )
        );

        return $ids ?? [];
    }

    private function getTaskIds(): ?array
    {
        if ($this->task_ids === false) {
            if ($this->task_view === 'column') {
                if ($this->task_status === 'open') {
                    $this->task_ids = $this->getOpenTaskIds();
                } elseif ($this->task_status === 'completed') {
                    $this->task_ids = $this->getCompletedTaskIds(false, 100);
                } else {
                    return array_merge(
                        $this->getOpenTaskIds(),
                        $this->getCompletedTaskIds(false, 100)
                    );
                }
            } elseif ($this->task_view === 'mobile' && $this->task_status === 'completed') {
                $this->task_ids = $this->getCompletedTaskIds();
            }
            else {
                // list and timeline view needs open tasks and closed tasks from opened lists
                if ($this->task_status === 'open') {
                    $this->task_ids = $this->getOpenTaskIds();
                } elseif ($this->task_status === 'completed') {
                    $this->task_ids = $this->getCompletedTaskIds(true);
                } else {
                    return array_merge($this->getOpenTaskIds(), $this->getCompletedTaskIds(true));
                }
            }
        }

        return $this->task_ids;
    }

    private function getTasksCollection(array $task_ids): ?DBResult
    {
        if ($this->tasks_collection === false) {
            if (empty($task_ids)) {
                $this->tasks_collection = null;
            } else {
                $this->tasks_collection = DB::execute(
                    'SELECT t.id, t.name, t.assignee_id, t.start_on, t.due_on, t.completed_on, t.position, t.task_list_id, t.project_id, t.is_hidden_from_clients, t.is_important, t.is_billable, t.is_trashed, t.task_number, t.created_from_recurring_task_id, t.estimate, t.job_type_id
                        FROM tasks t
                            LEFT JOIN task_lists tl ON tl.id = t.task_list_id
                        WHERE t.id IN (?)
                        ORDER BY tl.position ASC, t.position ASC',
                    $task_ids
                );
            }
        }

        return $this->tasks_collection;
    }

    public function execute()
    {
        $task_ids = $this->getTaskIds();
        $tasks = $this->getTasksCollection($task_ids);

        if (empty($tasks)) {
            return [];
        }

        $result = [];

        if ($task_ids) {
            Comments::preloadCountByParents(Task::class, $task_ids);
            Subtasks::preloadCountByTasks($task_ids);
            Labels::preloadIdsByParents(Task::class, $task_ids);
            TaskDependencies::preloadCountByTasks($task_ids);
        }

        foreach ($tasks as $task) {
            $result[] = [
                'id' => (int) $task['id'],
                'name' => $task['name'],
                'class' => 'Task',
                'assignee_id' => (int) $task['assignee_id'],
                'start_on' => $task['start_on'] ? new DateValue($task['start_on']) : null,
                'due_on' => $task['due_on'] ? new DateValue($task['due_on']) : null,
                'completed_on' => $task['completed_on'] ? new DateTimeValue($task['completed_on']) : null,
                'is_completed' => $task['completed_on'] !== null,
                'position' => (int) $task['position'],
                'task_list_id' => (int) $task['task_list_id'],
                'project_id' => (int) $task['project_id'],
                'is_hidden_from_clients' => $task['is_hidden_from_clients'],
                'is_important' => $task['is_important'],
                'is_trashed' => $task['is_trashed'],
                'is_billable' => $task['is_billable'],
                'task_number' => (int) $task['task_number'],
                'estimate' => $task['estimate'],
                'job_type_id' => (int) $task['job_type_id'],
                'created_from_recurring_task_id' => (int) $task['created_from_recurring_task_id'],
                'comments_count' => Comments::countByParentTypeAndParentId(Task::class, (int) $task['id']),
                'open_subtasks' => Subtasks::countByTaskId((int) $task['id'], $task['completed_on'] !== null)[1],
                'labels' => Labels::getIdsByParentTypeAndParentId(Task::class, (int) $task['id']),
                'open_dependencies' => TaskDependencies::countOpenDependenciesFromPreloadedValue((int) $task['id']),
                 'url_path' => sprintf(
                     '/projects/%s/tasks/%s',
                     $task['project_id'],
                     $task['id']
                 ),
            ];
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
                $this->getTasksTimestampHash()
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
}
