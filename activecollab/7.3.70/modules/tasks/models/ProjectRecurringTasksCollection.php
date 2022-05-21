<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Project recurring tasks collection.
 *
 * @package ActiveCollab.modules.task
 * @subpackage models
 */
class ProjectRecurringTasksCollection extends CompositeCollection
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
    private $recurring_tasks_collection = false;

    /**
     * @var int[]
     */
    private $recurring_task_ids = false;

    public function getModelName(): string
    {
        return 'RecurringTasks';
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
                $this->getRecurringTasksCollection()->getTimestampHash('updated_on') . '-' .
                $this->project->getUpdatedOn()->toMySQL()
            );
        }

        return $this->timestamp_hash;
    }

    /**
     * @return ModelCollection
     */
    private function getRecurringTasksCollection()
    {
        if ($this->recurring_tasks_collection === false) {
            $this->recurring_tasks_collection = RecurringTasks::prepareCollection('all_recurring_tasks_in_project_' . $this->project->getId(), $this->getWhosAsking());
        }

        return $this->recurring_tasks_collection;
    }

    /**
     * @return array
     */
    public function execute()
    {
        if ($ids = $this->getRecurringTaskIds()) {
            Attachments::preloadDetailsByParents('RecurringTask', $ids);
            Labels::preloadDetailsByParents('RecurringTask', $ids);
        }

        return [
            'recurring_tasks' => $this->getRecurringTasksCollection(),
            'label_ids' => Labels::getLabelIdsByProject($this->project),
            'project' => $this->project,
        ];
    }

    /**
     * Return list of task ID-s that are captured by this collection.
     *
     * @return array|null
     */
    private function getRecurringTaskIds()
    {
        if ($this->recurring_task_ids === false) {
            $this->recurring_task_ids = DB::executeFirstColumn('SELECT id FROM recurring_tasks WHERE project_id = ? AND is_trashed = ?', $this->project->getId(), false);
        }

        return $this->recurring_task_ids;
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($ids = $this->getRecurringTaskIds()) {
            return count($ids);
        }

        return 0;
    }
}
