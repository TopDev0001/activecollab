<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents\SubtaskMoveToTrashEvent;

final class Subtask extends BaseSubtask implements RoutingContextInterface
{
    use RoutingContextImplementation;

    /**
     * Set parent task.
     */
    public function setTask(Task $task)
    {
        $this->setTaskId($task->getId());
    }

    /**
     * Returns true if $user can access this task.
     */
    public function canView(User $user): bool
    {
        return $this->getTask() instanceof Task && $this->getTask()->canView($user);
    }

    /**
     * Return parent task.
     *
     * @return Task|DataObject
     */
    public function &getTask()
    {
        return DataObjectPool::get(Task::class, $this->getTaskId());
    }

    /**
     * Return true if $user can edit this object.
     */
    public function canEdit(User $user): bool
    {
        return $this->getTask() instanceof Task && ($this->getAssigneeId() == $user->getId() || $this->getTask()->canEdit($user));
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    /**
     * Returns true only $user can delete parent object.
     */
    public function canDelete(User $user): bool
    {
        return $this->getTask() instanceof Task && $this->getTask()->canEdit($user);
    }

    public function getRoutingContext(): string
    {
        return 'subtask';
    }

    public function getRoutingContextParams(): array
    {
        return array_merge(
            $this->getTask()->getRoutingContextParams(),
            [
                'subtask_id' => $this->getId(),
            ]
        );
    }

    // ---------------------------------------------------
    //  Interfaces implementation
    // ---------------------------------------------------

    public function save()
    {
        $count_cache_affected = $this->isCountCacheAffected();
        $search_index_affected = $this->isSearchIndexAffected();

        $task = $this->getTask();

        if (!$this->getPosition() && $task instanceof Task) {
            $this->setPosition(Subtasks::nextPositionByTask($task));
        }

        parent::save();

        if ($task && !$task->getIsTrashed()) {
            if ($count_cache_affected) {
                $task->touch();
            }

            if ($search_index_affected) {
                AngieApplication::search()->update($task);
            }
        }
    }

    private function isCountCacheAffected()
    {
        return $this->isNew()
            || $this->isModifiedField('body')
            || $this->isModifiedField('position')
            || $this->isModifiedField('is_trashed')
            || $this->isModifiedField('completed_on')
            || $this->isModifiedField('assignee_id');
    }

    /**
     * Return true if changes that are in this object affect parent's search index.
     *
     * @return bool
     */
    private function isSearchIndexAffected()
    {
        return $this->isNew()
            || $this->isModifiedField('is_trashed')
            || $this->isModifiedField('body');
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        if (!$this->validatePresenceOf('task_id')) {
            $errors->addError('Task is required', 'task_id');
        }

        if ($this->isModifiedField('completed_on') && $this->getFieldValue('completed_on') === null) {
            /** @var Task $task */
            $task = Tasks::findById($this->getTaskId());

            if ($task->isCompleted()) {
                $errors->addError(
                    lang('Cannot reopen subtask if task is still completed.'),
                    'completed_on'
                );
            }
        }

        if (!$this->validatePresenceOf('body')) {
            $errors->addError('Subtask text is required', 'body');
        }
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------
    /**
     * Move to trash.
     *
     * @param bool $bulk
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: Move subtask to trash @ ' . __CLASS__);

            Notifications::deleteByParentAndAdditionalProperty($this->getTask(), 'subtask_id', $this->getId());
            parent::moveToTrash($by, $bulk);

            DataObjectPool::announce(new SubtaskMoveToTrashEvent($this));

            DB::commit('Done: Move subtask to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: Move subtask to trash @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Delete subtask from database.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Deleting subtask @ ' . __CLASS__);

            Notifications::deleteByParentAndAdditionalProperty($this->getTask(), 'subtask_id', $this->getId());
            parent::delete($bulk);

            DB::commit('Subtask deleted @ ' . __CLASS__);

            if (empty($bulk)) {
                AngieApplication::search()->update($this->getTask());
            }
        } catch (Exception $e) {
            DB::rollback('Failed to delete subtask @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['name'] = $this->getName();
        $result['task_id'] = $this->getTaskId();
        $result['project_id'] = $this->getProjectId();
        $result['due_on'] = $this->getDueOn();
        $result['position'] = $this->getPosition();
        $result['fake_assignee_name'] = $this->getFakeAssigneeName();
        $result['fake_assignee_email'] = $this->getFakeAssigneeEmail();

        unset($result['body']);
        unset($result['body_formatted']);

        return $result;
    }

    /**
     * Return task name (first few words from text).
     *
     * @return string
     */
    public function getName()
    {
        return trim($this->getBody());
    }

    /**
     * Return project ID for the given subtask.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function getProjectId($use_cache = true)
    {
        return AngieApplication::cache()->getByObject($this, 'project_id', function () {
            return $this->getTask()->getProjectId();
        }, !$use_cache);
    }

    public function getTaskListId($use_cache = true)
    {
        return AngieApplication::cache()->getByObject($this, 'task_list_id', function () {
            return $this->getTask()->getTaskListId();
        }, !$use_cache);
    }

    // ---------------------------------------------------
    //  Activity logs
    // ---------------------------------------------------

    public function clearActivityLogs(): void
    {
        ActivityLogs::deleteByParentAndAdditionalProperty(
            $this->getTask(),
            'subtask_id',
            $this->getId()
        );
    }

    /**
     * Prepare and return creation log entry.
     *
     * @return ActivityLog|null
     */
    protected function getCreatedActivityLog()
    {
        $log = new SubtaskCreatedActivityLog();
        $log->setParent($this->getTask());
        $log->setParentPath($this->getTask()->getObjectPath());
        $log->setSubtask($this);

        $created_by = $this instanceof ICreatedBy && $this->getCreatedBy() instanceof IUser
            ? $this->getCreatedBy()
            : AngieApplication::authentication()->getAuthenticatedUser();

        if ($created_by instanceof IUser) {
            $log->setCreatedBy($created_by);
        }

        return $log;
    }

    /**
     * Prepare and return update log entry.
     *
     * @return ActivityLog|null
     */
    protected function getUpdatedActivityLog(array $modifications)
    {
        if ($remember = $this->getWhatIsWorthRemembering($modifications)) {
            $log = new SubtaskUpdatedActivityLog();

            $log->setParent($this->getTask());
            $log->setParentPath($this->getTask()->getObjectPath());
            $log->setSubtask($this);
            $log->setModifications($remember);

            $updated_by = $this instanceof IUpdatedBy ? $this->getUpdatedBy() : null;

            if (empty($updated_by)) {
                $updated_by = AngieApplication::authentication()->getAuthenticatedUser();
            }

            $log->setCreatedBy($updated_by);

            return $log;
        }

        return null;
    }

    /**
     * Return instance for created activity log.
     *
     * @return ActivityLog
     */
    protected function getCreatedActivityLogInstance()
    {
        return new SubtaskCreatedActivityLog();
    }

    /**
     * Return instance for updated activity log.
     *
     * @return ActivityLog
     */
    protected function getUpdatedActivityLogInstance()
    {
        return new SubtaskUpdatedActivityLog();
    }

    /**
     * Return which modifications should we remember.
     *
     * @return array
     */
    protected function whatIsWorthRemembering()
    {
        return Subtasks::whatIsWorthRemembering();
    }
}
