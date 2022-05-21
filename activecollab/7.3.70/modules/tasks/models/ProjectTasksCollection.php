<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project tasks collection.
 *
 * @package ActiveCollab.modules.task
 * @subpackage models
 */
class ProjectTasksCollection extends CompositeCollection
{
    use IWhosAsking;

    /**
     * @var Project
     */
    private $project;
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
     * @var ModelCollection
     */
    private $tasks_collection = false;
    private $task_lists_collection = false;

    /**
     * @var int[]
     */
    private $task_ids = false;

    /**
     * @var int[]
     */
    private $completed_task_ids = false;

    /**
     * @var int[]
     */
    private $trashed_task_ids = false;

    /**
     * @var int|null
     */
    private $task_max_updated_on = null;

    public function getModelName(): string
    {
        return 'Tasks';
    }

    /**
     * @return $this
     */
    public function &setProject(Project $project)
    {
        $this->project = $project;

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
            $this->tag = $this->prepareTagFromBits($user->getEmail(), $this->getTimestampHash());
        }

        return $this->tag;
    }

    /**
     * @return string
     */
    private function getTimestampHash()
    {
        if ($this->timestamp_hash === false) {
            $this->timestamp_hash = sha1(
                $this->getTasksCollection()->getTimestampHash('updated_on') . '-' .
                $this->getTaskListsCollection()->getTimestampHash('updated_on') . '-' .
                $this->project->getUpdatedOn()->toMySQL()
            );
        }

        return $this->timestamp_hash;
    }

    /**
     * @return ModelCollection
     */
    private function getTasksCollection()
    {
        if ($this->tasks_collection === false) {
            $this->tasks_collection = Tasks::prepareCollection('active_tasks_in_project_' . $this->project->getId(), $this->getWhosAsking());
        }

        return $this->tasks_collection;
    }

    /**
     * @return ModelCollection
     */
    private function getTaskListsCollection()
    {
        if ($this->task_lists_collection === false) {
            $this->task_lists_collection = TaskLists::prepareCollection('all_task_lists_in_project_' . $this->project->getId(), $this->getWhosAsking());
        }

        return $this->task_lists_collection;
    }

    /**
     * @return array
     */
    public function execute()
    {
        if ($task_ids = $this->getTaskIds()) {
            Comments::preloadCountByParents('Task', $task_ids);
            Subtasks::preloadCountByTasks($task_ids);
            Attachments::preloadDetailsByParents('Task', $task_ids);
            Labels::preloadDetailsByParents('Task', $task_ids);
            TaskDependencies::preloadCountByTasks($task_ids);
        }

        return [
            'tasks' => $this->getTasksCollection(),
            'task_lists' => $this->getTaskListsCollection(),
            'label_ids' => Labels::getLabelIdsByProject($this->project),
            'project' => $this->project,
            'completed_task_ids' => $this->getCompletedTaskIds(),
            'completed_tasks_count' => count($this->getCompletedTaskIds()),
            'trashed_task_ids' => $this->getTrashedTaskIds(),
            'task_max_updated_on' => $this->getTaskMaxUpdateOn(),
        ];
    }

    /**
     * Return list of task ID-s that are captured by this collection.
     *
     * @return array|null
     */
    private function getTaskIds()
    {
        if ($this->task_ids === false) {
            $this->task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ? AND completed_on IS NULL AND is_trashed = ?', $this->project->getId(), false);
        }

        return $this->task_ids;
    }

    /**
     * Return list of complted task ID-s for project.
     *
     * @return array|null
     */
    private function getCompletedTaskIds()
    {
        if ($this->completed_task_ids === false) {
            $ids = DB::executeFirstColumn(
                'SELECT id FROM tasks WHERE project_id = ? AND completed_on IS NOT NULL AND is_trashed = ?',
                $this->project->getId(),
                false
            );

            $this->completed_task_ids = $ids ? $ids : [];
        }

        return $this->completed_task_ids;
    }

    /**
     * Return list of trashed task ID-s for project.
     *
     * @return array|null
     */
    private function getTrashedTaskIds()
    {
        if ($this->trashed_task_ids === false) {
            $ids = DB::executeFirstColumn(
                'SELECT id FROM tasks WHERE project_id = ? AND is_trashed = ?',
                $this->project->getId(),
                true
            );

            $this->trashed_task_ids = $ids ? $ids : [];
        }

        return $this->trashed_task_ids;
    }

    /**
     * Return max updated on timestamp of active tasks.
     *
     * @return int|null
     */
    private function getTaskMaxUpdateOn()
    {
        if ($this->task_max_updated_on === null) {
            $update_on = DB::executeFirstCell(
                'SELECT MAX(updated_on) as "update_on" FROM tasks WHERE project_id = ? AND completed_on IS NULL AND is_trashed = ?',
                $this->project->getId(),
                false
            );

            $this->task_max_updated_on = $update_on ? (new DateTimeValue($update_on))->getTimestamp() : null;
        }

        return $this->task_max_updated_on;
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($task_ids = $this->getTaskIds()) {
            return count($task_ids);
        }

        return 0;
    }
}
