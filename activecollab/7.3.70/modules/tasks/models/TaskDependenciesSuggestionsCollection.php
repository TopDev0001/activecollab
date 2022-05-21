<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class TaskDependenciesSuggestionsCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var Task
     */
    private $task;

    /**
     * Cached tag value.
     *
     * @var string
     */
    private $tag = false;

    /**
     * @var ModelCollection
     */
    private $tasks_collection = false;
    private $task_lists_collection = false;

    /**
     * @var string
     */
    private $timestamp_hash = false;

    public function getModelName(): string
    {
        return 'TaskDependencies';
    }

    /**
     * @return $this
     */
    public function &setTask(Task $task)
    {
        $this->task = $task;

        return $this;
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
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash($user));
        }

        return $this->tag;
    }

    private function getTimestampHash(IUser $user): string
    {
        if ($this->timestamp_hash === false) {
            $this->timestamp_hash = sha1(
                $this->getTasksCollection()->getTimestampHash('updated_on') . '-' .
                $this->getTaskListsCollection()->getTimestampHash('updated_on') . '-' .
                $this->getTimestampHashForRecentTasks($user)
            );
        }

        return $this->timestamp_hash;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $tasks_collection = $this->getTasksCollection() ? $this->getTasksCollection() : null;

        $filtered_tasks = [];

        if ($tasks_collection) {
            /** @var Task[] $tasks */
            $tasks = $tasks_collection->execute();

            if (!empty($tasks)) {
                foreach ($tasks as $task) {
                    $filtered_tasks[] = [
                        'id' => $task->getId(),
                        'name' => $task->getName(),
                        'task_number' => $task->getTaskNumber(),
                        'task_list_id' => $task->getTaskListId(),
                    ];
                }
            }
        }

        return [
            'tasks' => $filtered_tasks,
            'task_lists' => $this->getTaskListsCollection(),
            'recent_tasks' => $this->getLastVisitedTaskIds($filtered_tasks),
        ];
    }

    public function count()
    {
        return $this->getTaskListsCollection()->count() + $this->getTasksCollection()->count();
    }

    private function getTasksCollection()
    {
        if ($this->tasks_collection === false) {
            $this->tasks_collection = Tasks::prepareCollection(
                'tasks_dependencies_suggestion_project_'  . $this->task->getProjectId() . '_task_' . $this->task->getId(),
                $this->getWhosAsking()
            );
        }

        return $this->tasks_collection;
    }

    private function getTaskListsCollection()
    {
        if ($this->task_lists_collection === false) {
            $this->task_lists_collection = TaskLists::prepareCollection(
                'tasks_dependencies_suggestion_project_'  . $this->task->getProjectId() . '_task_' . $this->task->getId(),
                $this->getWhosAsking()
            );
        }

        return $this->task_lists_collection;
    }

    private function getLastVisitedTaskIds(array $filtered_tasks): array
    {
        return Tasks::getRecentlyVisitedTaskIds($filtered_tasks, $this->getWhosAsking());
    }

    private function getTimestampHashForRecentTasks(IUser $user): string
    {
        $suggestion_task_ids = AngieApplication::taskDependenciesResolver($user)->getTaskDependenciesSuggestionIds($this->task->getId(), $this->task->getProjectId(), $user);
        if ($suggestion_task_ids) {
            $result = DB::executeFirstCell('SELECT accessed_on FROM access_logs WHERE parent_type = ? AND parent_id IN (?) AND accessed_by_id = ? ORDER BY accessed_on DESC LIMIT 1', 'Task', $suggestion_task_ids, $user->getId());

            return $result ? sha1($result) : sha1('no-access-logs');
        }

        return sha1('no-access-logs');
    }
}
