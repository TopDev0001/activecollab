<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListCompletedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListCreatedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListMoveToTrashEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListReopenedEvent;
use Angie\Search\SearchDocument\SearchDocumentInterface;

final class TaskList extends BaseTaskList implements IInvoiceBasedOn, RoutingContextInterface, ICalendarFeedElement
{
    use RoutingContextImplementation;
    use ICalendarFeedElementImplementation;

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'start_on',
                'due_on',
            ]
        );
    }

    /**
     * Return true if this task list is hidden from clients.
     *
     * @return bool
     */
    public function getIsHiddenFromClients()
    {
        return false;
    }

    /**
     * Empty method (task lists can't be hidden from clients).
     *
     * @param  bool      $value
     * @return bool|void
     */
    public function setIsHiddenFromClients($value)
    {
    }

    /**
     * Override default set attributes method.
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        if (isset($attributes['to_be_determined']) && $attributes['to_be_determined']) {
            $attributes['start_on'] = null;
            $attributes['due_on'] = null;
        }

        parent::setAttributes($attributes);
    }

    public function complete(User $by, bool $bulk = false)
    {
        try {
            DB::beginWork('Begin: complete task list @ ' . __CLASS__);

            parent::complete($by, $bulk);

            if ($tasks = Tasks::find(['conditions' => ['task_list_id = ? AND completed_on IS NULL', $this->getId()], 'order' => 'position'])) {
                foreach ($tasks as $task) {
                    $task->complete($by, true);
                }
            }

            DataObjectPool::announce(new TaskListCompletedEvent($this));

            DB::commit('Done: complete task list @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: complete task list @ ' . __CLASS__);

            throw $e;
        }

        AngieApplication::cache()->removeByObject($this->getProject(), 'first_task_list_id');
    }

    public function open(User $by, bool $bulk = false, $open_related_tasks = true)
    {
        if ($this->isCompleted()) {
            try {
                DB::beginWork('Begin: Open task list @ ' . __CLASS__);

                /** @var Task[] $tasks */
                $tasks = Tasks::find(
                    [
                        'conditions' => [
                            'task_list_id = ? AND completed_on >= ?',
                            $this->getId(),
                            $this->getCompletedOn(),
                        ],
                        'order' => 'position',
                    ]
                );

                if ($open_related_tasks && !empty($tasks)) {
                    foreach ($tasks as $task) {
                        $task->open($by, true);
                    }
                }

                parent::open($by, $bulk);

                DataObjectPool::announce(new TaskListReopenedEvent($this));

                DB::commit('Done: Open task list @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Rollback: Open task list @ ' . __CLASS__);

                throw $e;
            }

            AngieApplication::cache()->removeByObject($this->getProject(), 'first_task_list_id');
        }
    }

    public function skipCalendarFeed(): bool
    {
        return $this->isToBeDetermined();
    }

    public function getCalendarFeedSummary(
        IUser $user,
        string $prefix = '',
        string $sufix = ''
    ): string
    {
        return $this->prepareNameForCalendarExport($user, $prefix, $sufix);
    }

    public function getCalendarFeedDateStart()
    {
        return $this->getStartOn();
    }

    public function getCalendarFeedDateEnd()
    {
        return $this->getDueOn()->advance(86400, false); // + 1 day
    }

    private function prepareNameForCalendarExport(
        IUser $user,
        string $summary_prefix,
        string $summary_sufix
    ): string
    {
        if ($this->isCompleted()) {
            $task_list_name = lang('Completed', null, true, $user->getLanguage()) . ': ' . $this->getName();
        } else {
            $task_list_name = $this->getName();
        }

        return $summary_prefix . $task_list_name . $summary_sufix;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        unset($result['due_on']);

        $result['start_on'] = $this->getStartOn();
        $result['due_on'] = $this->getDueOn();
        $result['position'] = $this->getPosition();
        $result['open_tasks'] = Tasks::countOpenByTaskList($this);
        $result['completed_tasks'] = Tasks::countCompletedByTaskList($this);

        return $result;
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new ProjectElementSearchDocument($this);
    }

    /**
     * Returns if this task list start and due dates are to be determined.
     *
     * @return bool
     */
    public function isToBeDetermined()
    {
        return empty($this->getDueOn());
    }

    /**
     * Advance for give number of seconts.
     *
     * @param int  $seconds
     * @param bool $save
     */
    public function advance($seconds, $save = false)
    {
        if ($seconds != 0) {
            $start_on = $this->getStartOn();
            $due_on = $this->getDueOn();

            $this->setStartOn($start_on->advance($seconds, false));
            $this->setDueOn($due_on->advance($seconds, false));

            if ($save) {
                $this->save();
            }
        }
    }

    /**
     * Save record to the database.
     */
    public function save()
    {
        $starts_on = $this->getStartOn();
        $due_on = $this->getDueOn();

        if ($starts_on instanceof DateValue && empty($due_on)) {
            $this->setDueOn($starts_on);
        } else {
            if ($due_on instanceof DateValue && empty($starts_on)) {
                $this->setStartOn($due_on);
            }
        }

        if ($this->isNew() && !$this->getPosition()) {
            $this->setPosition(TaskLists::getNextPositionInProject($this->getProjectId()));
        }

        if ($this->isNew() || $this->isModifiedField('name')) {
            $default_task_list_name = ConfigOptions::getValue('default_task_list_name');

            if ($this->isNew() && $this->getName() == $default_task_list_name) {
                $default_task_list_created = true;
            } elseif ($this->isLoaded() && $this->isModifiedField('name') && $this->getOldFieldValue('name') == $default_task_list_name) {
                $default_task_list_renamed = true;
            }
        }

        parent::save();

        if (!empty($default_task_list_name) && !empty($default_task_list_created)) {
            AngieApplication::log()->event('default_task_list_created', 'Default task list {default_task_list_name} created', [
                'default_task_list_name' => $default_task_list_name,
            ]);
        }

        if (!empty($default_task_list_name) && !empty($default_task_list_renamed)) {
            AngieApplication::log()->event('default_task_list_renamed', 'Default task list renamed from {old_name} to {new_name}', [
                'old_name' => $default_task_list_name,
                'new_name' => $this->getName(),
                'name_lifetime' => DateTimeValue::now()->getTimestamp() - $this->getCreatedOn()->getTimestamp(),
            ]);
        }
    }

    // ---------------------------------------------------
    //  Context
    // ---------------------------------------------------
    /**
     * Query tracking records.
     *
     * This function returns three elements: array of time records, array of expenses and project
     *
     * @return array
     */
    public function queryRecordsForNewInvoice(IUser $user = null)
    {
        if ($user instanceof User && $this->canView($user)) {
            return [TimeRecords::findByTaskList($this, TimeRecord::BILLABLE), Expenses::findByTaskList($this, Expense::BILLABLE)];
        } else {
            return [null, null];
        }
    }

    public function getRoutingContext(): string
    {
        return 'task_list';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getProjectId(),
            'task_list_id' => $this->getId(),
        ];
    }

    private array $task_duplication_map = [];

    public function copyToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    ): IProjectElement
    {
        $this->task_duplication_map = [];

        try {
            DB::beginWork('Begin: copy task list to project @ ' . __CLASS__);

            // set next position (but not save!) which will be copy to new task list
            $this->setPosition(TaskLists::findNextPositionInProject($project));

            /** @var TaskList $task_list_copy */
            $task_list_copy = parent::copyToProject($project, $by, $before_save, $after_save);

            $task_list_copy = DataObjectPool::reload(TaskList::class, $task_list_copy->getId());

            DataObjectPool::announce(new TaskListCreatedEvent($task_list_copy));

            /** @var Task[] $tasks */
            $tasks = Tasks::find(
                [
                    'conditions' => [
                        'task_list_id = ? AND is_trashed = ?',
                        $this->getId(),
                        false,
                    ],
                    'order' => 'position',
                ]
            );

            if ($tasks) {
                foreach ($tasks as $task) {
                    // set temporary project id (but not save!) because we need to skeep copying dependencies
                    $task->setProjectId(0);

                    $this->task_duplication_map[$task->getId()] = $task->copyToProject(
                        $project,
                        $by,
                        function (Task &$task_copy) use ($task_list_copy) {
                            $task_copy->setTaskListId($task_list_copy->getId());

                            $task_copy->setCompletedOn(null);
                            $task_copy->setCompletedById(null);
                            $task_copy->setCompletedByName(null);
                            $task_copy->setCompletedByEmail(null);
                        },
                        function (Task $task_copy) use ($task) {
                            // back project id to previous value
                            $task->setProjectId($task_copy->getProjectId());
                        }
                    );
                }
            }

            DB::commit('Done: copy task list to project @ ' . __CLASS__);

            return $task_list_copy;
        } catch (Exception $e) {
            DB::rollback('Rollback: copy task list to project @ ' . __CLASS__);

            throw $e;
        }
    }

    public function duplicate(User $by, string $new_list_name = null): TaskList
    {
        if (empty($new_list_name)) {
            $new_list_name = sprintf('%s Copy', $this->getName());
        }

        $project = $this->getProject();

        try {
            DB::beginWork('Duplicate task list @ ' . __CLASS__);

            $copy = $this->copyToProject(
                $project,
                $by,
                function (TaskList $duplicated_task_list) use ($new_list_name) {
                    $duplicated_task_list->setName($new_list_name);
                },
                function ($duplicated_task_list) use ($by, $project) {
                    $order_ids = DB::executeFirstColumn(
                        'SELECT `id` FROM `task_lists` WHERE `project_id` = ? AND `id` <> ? ORDER BY `position`',
                        $project->getId(),
                        $duplicated_task_list->getId()
                    );

                    // do reorder if there are more then one task list in project
                    if ($order_ids && count($order_ids) > 1) {
                        $offset = array_search($this->getId(), $order_ids);
                        array_splice($order_ids, $offset + 1, 0, [$duplicated_task_list->getId()]);
                        TaskLists::reorder($order_ids, $project, $by);
                    }
                }
            );

            if (!empty($this->task_duplication_map)) {
                $original_task_ids = array_keys($this->task_duplication_map);

                // Copy only dependencies between items of the duplicated list. Dependencies that span between lists are not carried over.
                $internal_list_original_dependencies = DB::execute(
                    'SELECT `parent_id`, `child_id` FROM `task_dependencies` WHERE `parent_id` IN (?) AND `child_id` IN (?)',
                    $original_task_ids,
                    $original_task_ids
                );

                if (!empty($internal_list_original_dependencies)) {
                    foreach ($internal_list_original_dependencies as $original_dependency) {
                        /** @var Task $parent */
                        $parent = $this->task_duplication_map[$original_dependency['parent_id']];
                        /** @var Task $child */
                        $child = $this->task_duplication_map[$original_dependency['child_id']];

                        TaskDependencies::createDependency($parent, $child, $by);
                    }
                }
            }

            DB::commit('Task list duplicated @ ' . __CLASS__);

            return $copy;
        } catch (Exception $e) {
            DB::rollback('Failed to duplicate task list @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Move this task to project.
     */
    public function moveToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    )
    {
        $previous_project = $this->getProject();

        try {
            DB::beginWork('Begin: move task list to project @ ' . __CLASS__);

            $this->setPosition(TaskLists::findNextPositionInProject($this->getProject()));

            parent::moveToProject($project, $by, $before_save, $after_save);

            DataObjectPool::announce(new TaskListCreatedEvent($this));

            /** @var Task[] $tasks */
            if ($tasks = Tasks::find(['conditions' => ['task_list_id = ?', $this->getId()]])) {
                foreach ($tasks as $task) {
                    $task->moveToProject($project, $by, function (Task &$task_instance) {
                        $task_instance->setTaskListId($this->getId());
                    });
                }
            }

            DB::commit('Done: move task list to project @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move task list to project @ ' . __CLASS__);

            throw $e;
        }

        AngieApplication::cache()->removeByObject($previous_project, 'first_task_list_id');
    }

    /**
     * Move to trash.
     *
     * @param bool $bulk
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move task list to trash @ ' . __CLASS__);

            DB::execute('UPDATE tasks SET original_is_trashed = ? WHERE task_list_id = ? AND is_trashed = ?', true, $this->getId(), true); // Remember original is_trashed flag for already trashed elements

            if ($tasks = Tasks::find(['conditions' => ['task_list_id = ? AND is_trashed = ?', $this->getId(), false]])) {
                foreach ($tasks as $task) {
                    $task->moveToTrash($by, true);
                }
            }

            parent::moveToTrash($by, $bulk);

            DataObjectPool::announce(new TaskListMoveToTrashEvent($this));

            DB::commit('Done: move task list to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move task list to trash @ ' . __CLASS__);

            throw $e;
        }

        AngieApplication::cache()->removeByObject($this->getProject(), 'first_task_list_id');
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Restore from trash.
     *
     * @param bool $bulk
     */
    public function restoreFromTrash($bulk = false)
    {
        try {
            DB::beginWork('Begin: restore task list from trash @ ' . __CLASS__);

            if ($tasks = Tasks::find(['conditions' => ['task_list_id = ? AND is_trashed = ? AND original_is_trashed = ?', $this->getId(), true, false]])) {
                foreach ($tasks as $task) {
                    $task->restoreFromTrash(true);
                }
            }

            DB::execute('UPDATE tasks SET is_trashed = ?, original_is_trashed = ? WHERE task_list_id = ? AND original_is_trashed = ?', true, false, $this->getId(), true); // Restore previously trashed elements as trashed

            parent::restoreFromTrash($bulk);

            DB::commit('Done: restore task list from trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: restore task list from trash @ ' . __CLASS__);

            throw $e;
        }

        AngieApplication::cache()->removeByObject($this->getProject(), 'first_task_list_id');
    }

    /**
     * Remove from database.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: delete task list @ ' . __CLASS__);

            if ($task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE task_list_id = ?', $this->getId())) {
                $tasks = Tasks::findByIds($task_ids);

                foreach ($tasks as $task) {
                    $task->delete();
                }

                Tasks::clearCacheFor($task_ids);
            }

            parent::delete($bulk);

            DB::commit('Done: delete task list @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: delete task list @ ' . __CLASS__);

            throw $e;
        }

        AngieApplication::cache()->removeByObject($this->getProject(), 'first_task_list_id');

        Tasks::clearCache();
    }

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors &$errors)
    {
        $this->validatePresenceOf('name') or $errors->addError('List name is required', 'name');

        $start_on = $this->getStartOn();
        $due_on = $this->getDueOn();

        if ($start_on instanceof DateValue && $due_on instanceof DateValue) {
            if ($start_on->getTimestamp() > $due_on->getTimestamp()) {
                $errors->addError('Start date needs to be before due date', 'date_range');
            }
        }

        parent::validate($errors);
    }

    protected function whatIsWorthRemembering()
    {
        return TaskLists::whatIsWorthRemembering();
    }
}
