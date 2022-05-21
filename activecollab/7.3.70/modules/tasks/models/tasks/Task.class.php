<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\History\Renderers\DueOnHistoryFieldRenderer;
use ActiveCollab\Foundation\History\Renderers\EstimateHistoryFieldRenderer;
use ActiveCollab\Foundation\History\Renderers\IsBillableHistoryFieldRenderer;
use ActiveCollab\Foundation\History\Renderers\IsHiddenFromClientsHistoryFieldRenderer;
use ActiveCollab\Foundation\History\Renderers\IsImportantHistoryFieldRenderer;
use ActiveCollab\Foundation\History\Renderers\JobTypeHistoryFieldRenderer;
use ActiveCollab\Foundation\History\Renderers\StartOnHistoryFieldRenderer;
use ActiveCollab\Foundation\History\Renderers\TaskNumberHistoryFieldRenderer;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\System\Utils\DateValidationResolver\TaskDateValidationResolver;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskCreatedEvent;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskMoveToTrashEvent;
use Angie\Search\SearchDocument\SearchDocumentInterface;

class Task extends BaseTask implements IAssignees, IInvoiceBasedOn, RoutingContextInterface, ICalendarFeedElement
{
    use RoutingContextImplementation;
    use ICalendarFeedElementImplementation;

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'task_list_id',
                'task_number',
                'job_type_id',
                'estimate',
                'is_important',
                'is_billable',
                'start_on',
            ],
        );
    }

    public function getSearchFields(): array
    {
        return array_merge(
            parent::getSearchFields(),
            [
                'assignee_id',
            ],
        );
    }

    /**
     * Return task list instance, if set.
     *
     * @return TaskList|DataObject|null
     */
    public function getTaskList()
    {
        return DataObjectPool::get(TaskList::class, $this->getTaskListId());
    }

    /**
     * Set task list.
     */
    public function setTaskList(TaskList $task_list)
    {
        if (empty($task_list)) {
            throw new InvalidParamError(
                'task_list',
                $task_list,
                'Task list is expected to be a valid TaskList instance',
            );
        }

        if ($task_list->getId() != $this->getTaskListId()) {
            $this->setProjectId($task_list->getProjectId());
            $this->setTaskListId($task_list->getId());
        }
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['task_number'] = $this->getTaskNumber();
        $result['task_list_id'] = $this->getTaskListId();
        $result['position'] = $this->getPosition();
        $result['is_important'] = $this->getIsImportant();
        $result['start_on'] = $this->getStartOn();
        $result['due_on'] = $this->getDueOn();
        $result['estimate'] = $this->getEstimate();
        $result['job_type_id'] = $this->getJobTypeId();
        $result['fake_assignee_name'] = $this->getFakeAssigneeName();
        $result['fake_assignee_email'] = $this->getFakeAssigneeEmail();
        $result['is_billable'] = $this->getIsBillable();

        $result['total_subtasks'] = $this->countSubtasks();

        if ($result['total_subtasks']) {
            $result['open_subtasks'] = $this->countOpenSubtasks();
            $result['completed_subtasks'] = $result['total_subtasks'] - $result['open_subtasks'];
        } else {
            $result['open_subtasks'] = 0;
            $result['completed_subtasks'] = 0;
        }

        $result['created_from_recurring_task_id'] = $this->getCreatedFromRecurringTaskId();

        return $result;
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array &$result)
    {
        parent::describeSingleForFeather($result);

        $result['subtasks'] = $this->getSubtasks();
        $result['task_list'] = $this->getTaskList();
        $result['tracked_time'] = TimeRecords::sumByTask($this);
        $result['tracked_expenses'] = Expenses::sumByTask($this);

        if (empty($result['subtasks'])) {
            $result['subtasks'] = [];
        }
    }

    /**
     * Query tracking records.
     *
     * This function returns three elements: array of time records, array of expenses and project
     *
     * @param  User|IUser $user
     * @return array
     */
    public function queryRecordsForNewInvoice(IUser $user = null)
    {
        return [
            $this->getTimeRecords($user, TimeRecord::BILLABLE),
            $this->getExpenses($user, Expense::BILLABLE),
        ];
    }

    // ---------------------------------------------------
    //  Copy and move
    // ---------------------------------------------------

    public function copyToProject(
        Project $project,
        User $by,
        callable $before_save = null,
        callable $after_save = null
    ): IProjectElement
    {
        try {
            DB::beginWork('Begin: copy task to project @ ' . __CLASS__);

            $project_user_ids = $project->getMemberIds();

            $should_copy_dependencies = $this->getProjectId() === $project->getId();

            /** @var Task $task_copy */
            $task_copy = parent::copyToProject(
                $project,
                $by,
                function (Task $c) use ($project_user_ids, $before_save) {
                    if ($c->getAssigneeId() && !in_array($c->getAssigneeId(), $project_user_ids)) {
                        $c->setAssigneeId(0);
                        $c->setDelegatedById(0);
                    }

                    $c->setTaskNumber(0);

                    if ($before_save) {
                        call_user_func_array(
                            $before_save,
                            [
                                &$c,
                            ],
                        );
                    }
                },
                $after_save,
            );

            if (!($task_list = $task_copy->getTaskList()) || !$task_list instanceof TaskList || $task_list->getProjectId() != $project->getId()) {
                $task_copy->setTaskList(TaskLists::getFirstTaskList($project));
            }

            // set last position in new task list
            $task_copy->setPosition(Tasks::findNextPositionInTaskList($task_copy->getTaskList()));
            $task_copy->setCreatedFromRecurringTaskId(0); // duplicated task can't be created from recurring task
            $task_copy->save();

            $this->cloneSubtasksTo($task_copy, $project_user_ids);
            $this->cloneLabelsTo($task_copy);

            if ($should_copy_dependencies) {
                $this->cloneDependenciesTo($task_copy);
            }

            DB::commit('Done: copy task to project @ ' . __CLASS__);

            DataObjectPool::announce(new TaskCreatedEvent($task_copy));

            return $task_copy;
        } catch (Exception $e) {
            DB::rollback('Rollback: copy task to project @ ' . __CLASS__);

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
        if ($this->getProjectId() === $project->getId()) {
            return; // already in target $project
        }

        $project_user_ids = $project->getMemberIds();

        try {
            DB::beginWork('Begin: move task to project @ ' . __CLASS__);

            $task_list = TaskLists::getFirstTaskList($project);

            $this->setTaskListId($task_list->getId());
            $this->setTaskNumber(Tasks::findNextTaskNumberInProject($project->getId()));
            $this->setPosition(Tasks::findNextPositionInTaskList($task_list));
            $this->setCreatedFromRecurringTaskId(0);

            // Assignments clean up
            if ($this->getAssigneeId() && !in_array($this->getAssigneeId(), $project_user_ids)) {
                $this->setAssignee(null, null, false);
            }

            // Subtasks cleanup
            if ($subtasks = $this->getSubtasks(true)) {
                foreach ($subtasks as $subtask) {
                    if ($subtask->getAssigneeId() && !in_array($subtask->getAssigneeId(), $project_user_ids)) {
                        $subtask->setAssignee(null);
                    }
                }
            }

            parent::moveToProject($project, $by, $before_save, $after_save);

            DataObjectPool::announce(new TaskCreatedEvent($this));

            DB::commit('Done: move task to project @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move task to project @ ' . __CLASS__);

            throw $e;
        }
    }

    // ---------------------------------------------------
    //  Interface implementations
    // ---------------------------------------------------

    public function getRoutingContext(): string
    {
        return 'task';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getProjectId(),
            'task_id' => $this->getId(),
        ];
    }

    public function getLabelType(): string
    {
        return TaskLabel::class;
    }

    public function complete(User $by, bool $bulk = false)
    {
        if (empty($by)) {
            throw new InvalidInstanceError('by', $by, 'User');
        }

        try {
            DB::beginWork('Begin: Complete task @ ' . __CLASS__);

            parent::complete($by, $bulk);

            DB::execute(
                'UPDATE subtasks
                    SET `updated_on` = UTC_TIMESTAMP(), `completed_on` = ?, `completed_by_id` = ?, `completed_by_name` = ?, `completed_by_email` = ?
                    WHERE `task_id` = ? AND `completed_on` IS NULL',
                DateTimeValue::now(),
                $by->getId(),
                $by->getName(),
                $by->getEmail(),
                $this->getId(),
            );

            if ($task_list = $this->getTaskList()) {
                $task_list->touch();
            }

            DB::commit('Done: Complete task @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: Complete task @ ' . __CLASS__);

            throw $e;
        }
    }

    public function open(User $by, bool $bulk = false)
    {
        if (empty($by)) {
            throw new InvalidInstanceError('by', $by, 'User');
        }

        try {
            DB::beginWork('Begin: Open task @ ' . __CLASS__);

            $task_list = $this->getTaskList();

            if ($task_list->isCompleted()) {
                $task_list->open($by, true, false);
                AngieApplication::cache()->removeByObject($task_list);
            } else {
                $task_list->touch();
            }

            $task_completed_on = $this->getCompletedOn();

            parent::open($by, $bulk);

            if ($subtasks = Subtasks::findBySQL('SELECT * FROM subtasks WHERE task_id  = ? AND completed_on >= ? ORDER BY position', $this->getId(), $task_completed_on)) {
                foreach ($subtasks as $subtask) {
                    $subtask->open($by, true);
                }
            }

            DB::commit('Done: Open task @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: Open task @ ' . __CLASS__);

            throw $e;
        }
    }

    public function getHistoryFieldRenderers(): array
    {
        $renderers = parent::getHistoryFieldRenderers();

        $renderers['task_number'] = new TaskNumberHistoryFieldRenderer();
        $renderers['start_on'] = new StartOnHistoryFieldRenderer();
        $renderers['due_on'] = new DueOnHistoryFieldRenderer();
        $renderers['job_type_id'] = new JobTypeHistoryFieldRenderer();
        $renderers['estimate'] = new EstimateHistoryFieldRenderer();
        $renderers['is_hidden_from_clients'] = new IsHiddenFromClientsHistoryFieldRenderer();
        $renderers['is_important'] = new IsImportantHistoryFieldRenderer();
        $renderers['is_billable'] = new IsBillableHistoryFieldRenderer();

        return $renderers;
    }

    public function skipCalendarFeed(): bool
    {
        return empty($this->getStartOn()) || empty($this->getDueOn());
    }

    public function getCalendarFeedSummary(
        IUser $user,
        string $prefix = '',
        string $sufix = ''
    ): string
    {
        return $this->prepareNameForCalendarExport($user, $prefix, $sufix);
    }

    public function getCalendarFeedDescription(IUser $user): ?string
    {
        return lang(
            'Open this task in ActiveCollab: :task_url',
            [
                'task_url' => $this->getViewUrl(),
            ],
            true,
            $user->getLanguage(),
        );
    }

    public function getCalendarFeedDateStart()
    {
        return $this->getStartOn();
    }

    public function getCalendarFeedDateEnd()
    {
        return $this->getDueOn()->advance(86400, false); // +1 day
    }

    private function prepareNameForCalendarExport(
        IUser $user,
        string $summary_prefix,
        string $summary_sufix
    ): string
    {
        $task_number = '';

        if (ConfigOptions::getValue('show_task_id')) {
            if ($this->isCompleted()) {
                $task_number = lang(
                    'Completed Task #:task_number',
                    [
                        'task_number' => $this->getTaskNumber(),
                    ],
                    true,
                    $user->getLanguage(),
                );
            } else {
                $task_number = lang(
                    'Task #:task_number',
                    [
                        'task_number' => $this->getTaskNumber(),
                    ],
                    true,
                    $user->getLanguage(),
                );
            }
        }

        if ($task_number) {
            $task_name = sprintf('%s: %s', $task_number, $this->getName());
        } else {
            $task_name = $this->getName();
        }

        return $summary_prefix . $task_name . $summary_sufix;
    }

    // ---------------------------------------------------
    //  Estimates
    // ---------------------------------------------------

    /**
     * Return task job type.
     *
     * @return JobType|DataObject|null
     */
    public function getJobType()
    {
        return DataObjectPool::get(JobType::class, $this->getJobTypeId());
    }

    /**
     * Set job type.
     *
     * @param JobType|null $job_type
     */
    public function setJobType($job_type)
    {
        if ($job_type instanceof JobType) {
            $this->setJobTypeId($job_type->getId());
        } elseif ($job_type === null) {
            $this->setJobTypeId(null);
        } else {
            throw new InvalidInstanceError('job_type', $job_type, JobType::class);
        }
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new TaskSearchDocument($this);
    }

    // ---------------------------------------------------
    //  Subtasks
    // ---------------------------------------------------

    public function getSubtasks(bool $include_trashed = false): ?iterable
    {
        return Subtasks::findByTask($this, $include_trashed);
    }

    /**
     * Return a list of open subtasks.
     *
     * @return DBResult|Subtask[]
     */
    public function getOpenSubtasks()
    {
        return Subtasks::findOpenByTask($this);
    }

    /**
     * Return a list of completed subtasks.
     *
     * @return DBResult|Subtask[]
     */
    public function getCompletedSubtasks()
    {
        return Subtasks::findCompletedByTask($this);
    }

    /**
     * Count subtasks.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countSubtasks($use_cache = true)
    {
        return AngieApplication::cache()->getByObject($this, 'subtasks_count', function () {
            return Subtasks::countByTask($this);
        }, !$use_cache)[0];
    }

    /**
     * Count open subtasks.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countOpenSubtasks($use_cache = true)
    {
        return AngieApplication::cache()->getByObject($this, 'subtasks_count', function () {
            return Subtasks::countByTask($this);
        }, !$use_cache)[1];
    }

    /**
     * Count completed subtasks.
     *
     * @param  bool $use_cache
     * @return int
     */
    public function countCompletedSubtasks($use_cache = true)
    {
        return $this->countSubtasks($use_cache) - $this->countOpenSubtasks($use_cache);
    }

    /**
     * Clone subtasks to a $to object.
     *
     * @param int[] $limit_user_ids
     */
    public function cloneSubtasksTo(Task $to, $limit_user_ids = [])
    {
        if (empty($limit_user_ids)) {
            $limit_user_ids = [];
        }

        $rows = DB::execute(
            'SELECT id, assignee_id, body, due_on, created_on, created_by_id, created_by_name, created_by_email, completed_on, position FROM subtasks WHERE task_id = ? AND is_trashed = ? ORDER BY position, created_on',
            $this->getId(),
            false,
        );

        if ($rows) {
            $next_position = Subtasks::nextPositionByTask($this);

            try {
                DB::beginWork('Moving subtasks @ ' . __CLASS__);

                $subtasks_batch = new DBBatchInsert(
                    'subtasks',
                    [
                        'task_id',
                        'assignee_id',
                        'body',
                        'due_on',
                        'created_on',
                        'created_by_id',
                        'created_by_name',
                        'created_by_email',
                        'position',
                    ],
                );
                $subscriptions_batch = new DBBatchInsert(
                    'subscriptions',
                    [
                        'parent_type',
                        'parent_id',
                        'user_id',
                        'subscribed_on',
                        'code',
                    ],
                );

                $now = DateTimeValue::now()->toMySQL();

                foreach ($rows as $row) {
                    // Reopened tasks should be appended to the list
                    $position = $row['completed_on'] ? $next_position++ : $row['position'];

                    // Make sure that user ID is allowed
                    $assignee_id = $row['assignee_id'] && in_array($row['assignee_id'], $limit_user_ids) ? $row['assignee_id'] : 0;

                    if (!empty($limit_user_ids)) {
                        $subscriber_ids = DB::executeFirstColumn(
                            'SELECT user_id FROM subscriptions WHERE parent_type = ? AND parent_id = ? AND user_id IN (?)',
                            Subtask::class,
                            $row['id'],
                            $limit_user_ids,
                        );
                    } else {
                        $subscriber_ids = [];
                    }

                    // If we have subscribers, we'll need new subtask ID so we need to do the insert now
                    if (!empty($subscriber_ids)) {
                        DB::execute(
                            'INSERT INTO subtasks (task_id, assignee_id, body, due_on, created_on, created_by_id, created_by_name, created_by_email, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                            $to->getId(),
                            $assignee_id,
                            $row['body'],
                            $row['due_on'],
                            $row['created_on'],
                            $row['created_by_id'],
                            $row['created_by_name'],
                            $row['created_by_email'],
                            $position,
                        );

                        $new_subtask_id = DB::lastInsertId();

                        foreach ($subscriber_ids as $subscriber_id) {
                            $subscriptions_batch->insert(Subtask::class, $new_subtask_id, $subscriber_id, $now, make_string(10));
                        }

                        // No subscribers? Add subtask to batch
                    } else {
                        $subtasks_batch->insert(
                            $to->getId(),
                            $assignee_id,
                            $row['body'],
                            $row['due_on'],
                            $row['created_on'],
                            $row['created_by_id'],
                            $row['created_by_name'],
                            $row['created_by_email'],
                            $position,
                        );
                    }
                }

                $subtasks_batch->done();
                $subscriptions_batch->done();

                DB::commit('Subtasks moved @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Failed to move subtasks @ ' . __CLASS__);

                throw $e;
            }
        }
    }

    // ---------------------------------------------------
    //  Activity logs
    // ---------------------------------------------------

    /**
     * Return which modifications should we remember.
     *
     * @return array
     */
    protected function whatIsWorthRemembering()
    {
        return Tasks::whatIsWorthRemembering();
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors &$errors)
    {
        if (!$this->validatePresenceOf('name')) {
            $errors->addError('Task summary is required', 'name');
        }

        if (!$this->validateMaxValueOf('name', 150)) {
            $errors->addError('Task summary is too long', 'name');
        }

        $this->validatePresenceOf('task_list_id') or $errors->fieldValueIsRequired('task_list_id');

        if ($this->validatePresenceOf('estimate') && !$this->validatePresenceOf('job_type_id')) {
            $errors->addError('Job type is required for tasks with estimates', 'job_type_id');
        }

        $task_date_validation_resolver = AngieApplication::getContainer()->get(TaskDateValidationResolver::class);

        if (
            $this->validatePresenceOf('start_on') &&
            $this->getStartOn() instanceof DateValue &&
            !$task_date_validation_resolver->isValid($this->getStartOn())
        ) {
            $errors->addError('Invalid start date', 'start_on');
        }

        if (
            $this->validatePresenceOf('due_on') &&
            $this->getStartOn() instanceof DateValue &&
            !$task_date_validation_resolver->isValid($this->getDueOn())
        ) {
            $errors->addError('Invalid due date', 'due_on');
        }

        if ($this->getStartOn() && $this->getDueOn() && $this->getStartOn() > $this->getDueOn()) {
            $errors->addError('Start date should be before due date', 'start_on');
        }

        if ($this->getAssignee() && !$this->getProject()->isMember($this->getAssignee())) {
            $errors->addError(
                "Task can not be assigned to a member if he isn't on a project",
                'assignee_id',
            );
        }

        if ($this->getAssignee() instanceof Client && $this->getIsHiddenFromClients()) {
            $errors->addError("Task can not be assigned to a client if it's hidden from clients", 'assignee_id');
        }

        parent::validate($errors);
    }

    public function save()
    {
        if (!$this->getTaskListId()) {
            if ($project = $this->getProject()) {
                $this->setTaskListId(TaskLists::getFirstTaskListId($project));
            }
        }

        if ($this->isNew() || !$this->getTaskNumber()) {
            $this->setTaskNumber(Tasks::findNextTaskNumberInProject($this->getProjectId()));
        }

        if ($this->isNew() && !$this->getPosition()) {
            $this->setPosition(Tasks::findNextPositionInTaskList($this->getTaskListId()));
        }

        if ($this->isModifiedField('task_list_id')) {
            $clear_for = [];

            if ($this->getOldFieldValue('task_list_id')) {
                $clear_for[] = $this->getOldFieldValue('task_list_id');
            }

            if ($this->getFieldValue('task_list_id')) {
                $clear_for[] = $this->getFieldValue('task_list_id');
            }

            TaskLists::clearCacheFor($clear_for);
        }

        $hidden_from_clients_changed = $this->isModifiedField('is_hidden_from_clients');

        if ($this->getStartOn() && !$this->getDueOn()) {
            $this->setDueOn($this->getStartOn());
        } else {
            if ($this->getDueOn() && !$this->getStartOn()) {
                $this->setStartOn($this->getDueOn());
            }
        }

        if ($project = $this->getProject()) {
            if (
                $project->getBudgetType() === Project::BUDGET_NOT_BILLABLE ||
                ($this->isNew() && !$project->getIsBillable())
            ) {
                $this->setIsBillable(false);
            }
        }

        parent::save();

        if ($hidden_from_clients_changed) {
            $this->rebuildTrackingUpdates();
        }
    }

    /**
     * Create a task copy in a safe way.
     *
     * @param  bool $save
     * @return Task
     */
    public function copy($save = false)
    {
        /** @var Task $copy */
        $copy = parent::copy();
        $copy->setTaskNumber(0);

        if ($save) {
            $copy->save();
        }

        return $copy;
    }

    /**
     * Set value of specific field.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return mixed
     */
    public function setFieldValue($name, $value)
    {
        if ($name == 'estimate') {
            if ($value) {
                if (strpos($value, ':') !== false) {
                    $value = time_to_float($value);
                }

                if ($value > 0 && $value < 0.01) {
                    $value = 0.01;
                }
            } else {
                $value = 0; // Make sure that empty values are always stored as 0
            }
        }

        if ($name == 'job_type_id' && empty($value)) {
            $value = 0; // Make sure that empty values are always stored as 0
        }

        return parent::setFieldValue($name, $value);
    }

    /**
     * Move to trash.
     *
     * @param bool $bulk
     */
    public function moveToTrash(User $by = null, $bulk = false)
    {
        try {
            DB::beginWork('Begin: move task to trash @ ' . __CLASS__);

            Notifications::deleteByParent($this);

            DB::execute('UPDATE subtasks SET original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE task_id = ? AND is_trashed = ?', true, $this->getId(), true); // Remember original is_trashed flag for already trashed subtask
            DB::execute('UPDATE subtasks SET is_trashed = ?, trashed_on = ?, trashed_by_id = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE task_id = ? AND is_trashed = ?', true, DateTimeValue::now(), ($by instanceof User ? $by->getId() : AngieApplication::authentication()->getLoggedUserId()), false, $this->getId(), false); // Trash subtasks that are not already in trash

            if (!$bulk && $task_list = $this->getTaskList()) {
                $task_list->touch();
            }

            parent::moveToTrash($by, $bulk);

            DataObjectPool::announce(new TaskMoveToTrashEvent($this));

            DB::commit('Done: move task to trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: move task to trash @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Restore from trash.
     *
     * @param bool $bulk
     */
    public function restoreFromTrash($bulk = false)
    {
        try {
            DB::beginWork('Begin: restored from trash @ ' . __CLASS__);

            if (empty($bulk) && $this->getTaskList()->getIsTrashed()) {
                throw new RestoreFromTrashError();
            }

            DB::execute('UPDATE subtasks SET is_trashed = ?, trashed_on = NULL, trashed_by_id = ?, updated_on = UTC_TIMESTAMP() WHERE task_id = ? AND original_is_trashed = ?', false, 0, $this->getId(), false);
            DB::execute('UPDATE subtasks SET is_trashed = ?, original_is_trashed = ?, updated_on = UTC_TIMESTAMP() WHERE task_id = ? AND is_trashed = ?', true, false, $this->getId(), true);

            if (!$bulk && $task_list = $this->getTaskList()) {
                $task_list->touch();
            }

            parent::restoreFromTrash($bulk);

            DB::commit('Done: restored from trash @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: restored from trash @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Delete this task.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: delete task @ ' . __CLASS__);

            parent::delete($bulk);
            Subtasks::deleteByTask($this);
            TaskDependencies::deleteByTask($this);
            Stopwatches::deleteByTask($this);

            DB::commit('Done: delete task @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: delete task @ ' . __CLASS__);

            throw $e;
        }
    }

    public function canEdit(User $user): bool
    {
        return $this->canView($user) && ($this->isCreatedBy($user) || $user->isMember() || $user->isPowerClient(true));
    }

    public function canChangeCompletionStatus(User $user)
    {
        $project = $this->getProject();

        if ($project instanceof IComplete && $project->isCompleted()) {
            return false;
        }

        return $this->canEdit($user);
    }
}
