<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class AssignmentsCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

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
     * Return timestamp hash.
     *
     * @return string
     */
    public function getTimestampHash()
    {
        return sha1(
            implode(
                ',',
                [
                    $this->getContextTimestamp(),
                    $this->getProjectsTimestamp(),
                    $this->getTasksCollections()->getTimestampHash('updated_on'),
                    $this->getSubtasksCollection()->getTimestampHash('updated_on'),
                ]
            )
        );
    }

    abstract public function getContextTimestamp(): string;

    /**
     * @return string
     */
    private function getProjectsTimestamp()
    {
        return DB::executeFirstCell('SELECT MAX(`updated_on`) FROM `projects`');
    }

    abstract protected function getTasksCollections(): ModelCollection;
    abstract protected function getSubtasksCollection(): ModelCollection;

    /**
     * @return array
     */
    public function execute()
    {
        $type_ids_map = [
            Project::class => [],
            TaskList::class => [],
            Task::class => [],
        ];

        /** @var Task[] $tasks */
        if ($tasks = $this->getTasksCollections()->execute()) {
            foreach ($tasks as $task) {
                if (!in_array($task->getProjectId(), $type_ids_map[Project::class])) {
                    $type_ids_map[Project::class][] = $task->getProjectId();
                }

                $task_list_id = $task->getTaskListId();

                if ($task_list_id && !in_array($task_list_id, $type_ids_map[TaskList::class])) {
                    $type_ids_map[TaskList::class][] = $task_list_id;
                }
            }
        }

        /** @var Subtask[] $subtasks */
        if ($subtasks = $this->getSubtasksCollection()->execute()) {
            foreach ($subtasks as $subtask) {
                if (!in_array($subtask->getProjectId(), $type_ids_map[Project::class])) {
                    $type_ids_map[Project::class][] = $subtask->getProjectId();
                }

                $task_list_id = $subtask->getTaskListId();

                if ($task_list_id && !in_array($task_list_id, $type_ids_map[TaskList::class])) {
                    $type_ids_map[TaskList::class][] = $task_list_id;
                }

                if (!in_array($subtask->getTaskId(), $type_ids_map[Task::class])) {
                    $type_ids_map[Task::class][] = $subtask->getTaskId();
                }
            }
        }

        foreach ($type_ids_map as $k => $v) {
            if (empty($v)) {
                unset($type_ids_map[$k]);
            }
        }

        // preload releated projects counts
        if (isset($type_ids_map[Project::class])) {
            Projects::preloadProjectElementCounts($type_ids_map[Project::class]);
        }
        // preload releated task lists counts
        if (isset($type_ids_map[TaskList::class])) {
            Tasks::preloadCountByTaskList($type_ids_map[TaskList::class]);
        }
        // preload releated tasks counts
        if (isset($type_ids_map[Task::class])) {
            Comments::preloadCountByParents(Task::class, $type_ids_map[Task::class]);
            Subtasks::preloadCountByTasks($type_ids_map[Task::class]);
            Attachments::preloadDetailsByParents(Task::class, $type_ids_map[Task::class]);
            Labels::preloadDetailsByParents(Task::class, $type_ids_map[Task::class]);
            TaskDependencies::preloadCountByTasks($type_ids_map[Task::class]);
        }

        $result = [
            'tasks' => $tasks,
            'subtasks' => $subtasks,
            'related' => count($type_ids_map) ? DataObjectPool::getByTypeIdsMap($type_ids_map) : null,
        ];

        foreach ($result as $k => $v) {
            if (empty($v)) {
                $result[$k] = [];
            }
        }

        return $result;
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        return $this->getTasksCollections()->count() + $this->getSubtasksCollection()->count();
    }
}
