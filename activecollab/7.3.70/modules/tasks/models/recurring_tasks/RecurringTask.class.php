<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\Factory\RecurrenceIntervalFactoryInterface;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\RecurrenceIntervalInterface;
use ActiveCollab\Module\Tasks\Utils\TaskFromRecurringTaskProducer\TaskFromRecurringTaskProducerInterface;
use Angie\FeatureFlags\FeatureFlagsInterface;
use Angie\Search\SearchDocument\SearchDocumentInterface;

class RecurringTask extends BaseRecurringTask implements IAssignees, RoutingContextInterface, ICalendarFeedElement
{
    use RoutingContextImplementation;
    use ICalendarFeedElementImplementation;

    const REPEAT_FREQUENCY_NEVER = 'never';
    const REPEAT_FREQUENCY_DAILY = 'daily';
    const REPEAT_FREQUENCY_WEEKLY = 'weekly';
    const REPEAT_FREQUENCY_MONTHLY = 'monthly';
    const REPEAT_FREQUENCY_QUARTERLY = 'quarterly';
    const REPEAT_FREQUENCY_SEMIYEARLY = 'semiyearly';
    const REPEAT_FREQUENCY_YEARLY = 'yearly';

    const REPEAT_FREQUENCIES = [
        self::REPEAT_FREQUENCY_NEVER,
        self::REPEAT_FREQUENCY_DAILY,
        self::REPEAT_FREQUENCY_WEEKLY,
        self::REPEAT_FREQUENCY_MONTHLY,
        self::REPEAT_FREQUENCY_QUARTERLY,
        self::REPEAT_FREQUENCY_SEMIYEARLY,
        self::REPEAT_FREQUENCY_YEARLY,
    ];

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'project_id' => $this->getProjectId(),
                'task_list_id' => $this->getTaskListId(),
                'position' => $this->getPosition(),
                'is_important' => $this->getIsImportant(),
                'start_in' => $this->getStartIn(),
                'due_in' => $this->getDueIn(),
                'estimate' => $this->getEstimate(),
                'job_type_id' => $this->getJobTypeId(),
                'last_trigger_on' => $this->getLastTriggerOn(),
                'repeat_frequency' => $this->getRepeatFrequency(),
                'repeat_amount' => $this->getRepeatAmount(),
                'repeat_amount_extended' => $this->getRepeatAmountExtended(),
                'triggered_number' => $this->getTriggeredNumber(),
                'next_trigger_on' => $this->getNextTriggerOn(),
            ]
        );
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new ProjectElementSearchDocument($this);
    }

    public function registerRecurringTaskCreated(Task $task, DateValue $last_trigger_on): void
    {
        if ($task->getCreatedFromRecurringTaskId() != $this->getId()) {
            throw new LogicException('Input task was not created from this recurring task.');
        }

        $this->setTriggeredNumber($this->getTriggeredNumber() + 1);
        $this->setLastTriggerOn($last_trigger_on);
        $this->save();
    }

    public function removeAssignee(User $user, User $by): void
    {
        // if $user is assignee on task, remove assignee
        if ($this->getAssigneeId() === $user->getId()) {
            $this->setAssignee(null, $by);
        }

        // get all subtasks for recurring task and remove $user if assignee
        if ($recurring_subtasks = $this->getSubtasks()) {
            $this->setSubtasks(null);
            $new_subtasks = []; // new subtasks needs to be set

            foreach ($recurring_subtasks as $subtask) {
                $assignee_id = $subtask['assignee_id'];

                if ($assignee_id === $user->getId()) {
                    $assignee_id = 0;
                }

                $new_subtasks[] = [
                    'assignee_id' => $assignee_id,
                    'body' => $subtask['body'],
                ];
            }

            $this->setSubtasks($new_subtasks);
            $this->save();
        }
    }

    /**
     * Create subtasks based on recurring task.
     *
     * @param int   $task_id
     * @param array $subtasks
     */
    public function createSubtasksFromRecurringTask($task_id, $subtasks)
    {
        foreach ($subtasks as $subtask) {
            Subtasks::create(
                [
                    'task_id' => $task_id,
                    'assignee_id' => $subtask['assignee_id'],
                    'body' => $subtask['body'],
                    'created_by_id' => $this->getCreatedById(),
                    'created_by_name' => $this->getCreatedByName(),
                    'created_by_email' => $this->getCreatedByEmail(),
                ]
            );
        }
    }

    public function createTask(
        DateValue $trigger_date = null,
        User $created_by = null,
        string $override_name = null
    ): Task
    {
        $is_make_one = !empty($created_by);

        if ($this->shouldUseTaskFromRecurringTaskProducer()) {
            if ($is_make_one) {
                return AngieApplication::getContainer()
                    ->get(TaskFromRecurringTaskProducerInterface::class)
                        ->produceManually(
                            $this,
                            $created_by,
                            $trigger_date ?? DateTimeValue::now()->getSystemDate(),
                            $override_name
                        );
            } else {
                return AngieApplication::getContainer()
                    ->get(TaskFromRecurringTaskProducerInterface::class)
                        ->produceAutomatically(
                            $this,
                            $trigger_date ?? DateTimeValue::now()->getSystemDate()
                        );
            }
        } else {
            try {
                DB::beginWork('Begin: create task from recurring task @ ' . __CLASS__);

                $start_in = $this->getStartIn();
                $due_in = $this->getDueIn();
                $task_list_id = $this->getTaskListId();

                if ($trigger_date) {
                    $trigger_date_timestamp = $trigger_date->getTimestamp();
                } else {
                    $trigger_date_timestamp = DateValue::now()->getTimestamp();
                }

                if ($this->getRepeatFrequency() == self::REPEAT_FREQUENCY_DAILY && $this->getRepeatAmount() == 0) {
                    $range = $this->getStartDueOnRangeSkipWeekend($trigger_date_timestamp);

                    $start_in = $range['start_in'];
                    $due_in = $range['due_in'];
                }

                $start_on = null;
                $due_on = null;

                if (!empty($start_in) || !empty($due_in)) {
                    $start_on = DateValue::makeFromTimestamp(strtotime('+' . $start_in . 'day', $trigger_date_timestamp));
                }

                if (!empty($due_in)) {
                    $due_on = DateValue::makeFromTimestamp(strtotime('+' . $due_in . 'day', $trigger_date_timestamp));
                }

                if ($due_in === 0) {
                    $start_on = DateTimeValue::now()->getSystemDate();
                    $due_on = DateTimeValue::now()->getSystemDate();
                }

                /** @var TaskList $check_task_list */
                $check_task_list = TaskLists::findById($task_list_id);
                if (empty($check_task_list) || $check_task_list->isCompleted() || $check_task_list->getIsTrashed()) {
                    $task_list_id = TaskLists::getFirstTaskListId($this->getProject(), false);
                }

                $task = Tasks::create(
                    [
                        'project_id' => $this->getProjectId(),
                        'task_list_id' => $task_list_id,
                        'assignee_id' => $this->getAssigneeId(),
                        'name' => $override_name ?? $this->getName(),
                        'body' => $this->getBody(),
                        'body_mode' => $this->getBodyMode(),
                        'is_important' => $this->getIsImportant(),
                        'created_by_id' => $created_by ? $created_by->getId() : $this->getCreatedById(),
                        'created_by_name' => $created_by ? $created_by->getDisplayName() : $this->getCreatedByName(),
                        'created_by_email' => $created_by ? $created_by->getAdditionalEmailAddresses() : $this->getCreatedByEmail(),
                        'start_on' => $start_on,
                        'due_on' => $due_on,
                        'job_type_id' => $this->getJobTypeId(),
                        'estimate' => $this->getEstimate(),
                        'is_hidden_from_clients' => $this->getIsHiddenFromClients(),
                        'created_from_recurring_task_id' => $this->getId(),
                    ]
                );

                if (is_array($this->getSubtasks()) && count($this->getSubtasks())) {
                    $this->createSubtasksFromRecurringTask($task->getId(), $this->getSubtasks());
                }

                if ($trigger_date) {
                    $this->registerRecurringTaskCreated($task, $trigger_date);
                }

                $this->cloneLabelsTo($task);
                $this->cloneAttachmentsTo($task);
                $this->cloneSubscribersTo($task, $this->getSubscriberIds());

                DB::commit('Done: create task & subtasks from recurring task @ ' . __CLASS__);

                /** @var NewTaskNotification $notification */
                $notification = AngieApplication::notifications()->notifyAbout('tasks/new_task', $task, $created_by);
                $notification->sendToSubscribers();

                // Engagement + 1
                AngieApplication::log()->event(
                    'task_created_from_recurring_task',
                    'Task #{task_id} has been created from {repeat_frequency} recurring task #{recurring_task_id}',
                    [
                        'task_id' => $task->getId(),
                        'recurring_task_id' => $this->getId(),
                        'repeat_frequency' => $this->getRepeatFrequency(),
                        'manually_triggered_by' => $is_make_one ? $task->getCreatedById() : 0,
                    ]
                );

                return $task;
            } catch (Exception $e) {
                DB::rollback('Rollback: create task from recurring task @ ' . __CLASS__);
                throw $e;
            }
        }
    }

    private function shouldUseTaskFromRecurringTaskProducer(): bool
    {
        return AngieApplication::isInTestMode()
            || AngieApplication::getContainer()
                ->get(FeatureFlagsInterface::class)
                    ->isEnabled('new_task_from_recurring_task_producer');
    }

    /**
     * @param  int   $created_on
     * @param  bool  $reverse
     * @return array
     */
    public function getStartDueOnRangeSkipWeekend($created_on, $reverse = false)
    {
        if (empty($created_on)) {
            $created_on = $this->getCreatedOn()->getTimestamp();
        }

        $day_to_add = $reverse ? -1 : 1;
        $created_on_date = DateValue::makeFromTimestamp($created_on);

        $start_in = $day_to_add * $this->getStartIn();
        $start_date = AngieApplication::datesRescheduleCalculator()
            ->addDays(
                clone $created_on_date,
                $start_in
            );
        if ($start_in === 0) {
            while ($start_date->isWeekend()) {
                $start_date->addDays($day_to_add);
            }
        }

        $due_in = $day_to_add * $this->getDueIn();
        $due_date = AngieApplication::datesRescheduleCalculator()
            ->addDays(
                clone $created_on_date,
                $due_in
            );
        if ($due_in === 0) {
            while ($due_date->isWeekend()) {
                $due_date->addDays($day_to_add);
            }
        }

        return [
            'start_in' => $created_on_date->daysBetween($start_date),
            'due_in' => $created_on_date->daysBetween($due_date),
        ];
    }

    public function validate(ValidationErrors & $errors)
    {
        if (!$this->validatePresenceOf('name')) {
            $errors->fieldValueIsRequired('name');
        }

        if (!$this->validatePresenceOf('task_list_id')) {
            $errors->fieldValueIsRequired('task_list_id');
        }

        if ($this->validatePresenceOf('estimate') && !$this->validatePresenceOf('job_type_id')) {
            $errors->addError('Job type is required for recurring tasks with estimates', 'job_type_id');
        }

        if ($this->getStartIn() && $this->getDueIn() && $this->getStartIn() > $this->getDueIn()) {
            $errors->addError('Start date should be before due date', 'start_in');
        }

        $five_years = 5 * 365;

        if ($this->getStartIn() && $this->getStartIn() > $five_years) {
            $errors->addError('Invalid start in', 'start_in');
        }

        if ($this->getDueIn() && $this->getDueIn() > $five_years) {
            $errors->addError('Invalid due in', 'due_in');
        }

        try {
            $this->getRecurrenceInterval();
        } catch (InvalidArgumentException $e) {
            $errors->addError('Repeat frequency is not correct', 'repeat_frequency');
        }

        parent::validate($errors);
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

    public function save()
    {
        if ($this->isNew() && !$this->getPosition()) {
            $this->setPosition(RecurringTasks::findNextFieldValueByProject($this->getProjectId(), 'position'));
        }

        if ($this->getStartIn() >= 0 && $this->getDueIn() === null) {
            $this->setDueIn($this->getStartIn()); // if not specified due_in, it assumed to be same as start_in
        } elseif ($this->getStartIn() === null && $this->getDueIn() >= 0) {
            $this->setStartIn($this->getDueIn()); // if not specified start_in, it assumed to be as due_in
        }

        parent::save();
    }

    public function canEdit(User $user): bool
    {
        return $this->canView($user)
            && ($this->isCreatedBy($user) || $user->isMember() || $user->isPowerClient(true));
    }

    public function getSubtasks(bool $include_trashed = false): ?iterable
    {
        $recurring_subtasks = $this->getAdditionalProperty('recurring_subtasks');

        if (empty($recurring_subtasks) || !is_array($recurring_subtasks)) {
            $recurring_subtasks = null;
        }

        return $recurring_subtasks;
    }

    public function setSubtasks(?iterable $recurring_subtasks): ?iterable
    {
        if (empty($recurring_subtasks)) {
            $recurring_subtasks = null;
        }

        return $this->setAdditionalProperty('recurring_subtasks', $recurring_subtasks);
    }

    public function getRoutingContext(): string
    {
        return 'recurring_task';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'project_id' => $this->getProjectId(),
            'recurring_task_id' => $this->getId(),
        ];
    }

    public function getLabelType(): string
    {
        return TaskLabel::class;
    }

    /**
     * Return which modifications should we remember.
     *
     * @return array
     */
    protected function whatIsWorthRemembering()
    {
        return RecurringTasks::whatIsWorthRemembering();
    }

    public function getRecurrenceInterval(): RecurrenceIntervalInterface
    {
        $factory = AngieApplication::getContainer()->get(RecurrenceIntervalFactoryInterface::class);

        switch ($this->getRepeatFrequency()) {
            case self::REPEAT_FREQUENCY_NEVER:
                return $factory->never();
            case self::REPEAT_FREQUENCY_DAILY:
                return $factory->daily(empty($this->getRepeatAmount()));
            case self::REPEAT_FREQUENCY_WEEKLY:
                $weekday = $this->getRepeatAmount();

                // @TODO: Consider switching to 0..6 workday numbers. It now uses 1..6 for Mon to Sat, but Sun is 7!
                if ($weekday > 6) {
                    $weekday = 0;
                }

                return $factory->weekly(
                    [
                        $weekday,
                    ],
                    $this->getRepeatAmountExtended()
                );
            case self::REPEAT_FREQUENCY_MONTHLY:
                return $factory->monthly($this->getRepeatAmount());
            case self::REPEAT_FREQUENCY_QUARTERLY:
                return $factory->quarterly($this->getRepeatAmount(), $this->getRepeatAmountExtended());
            case self::REPEAT_FREQUENCY_SEMIYEARLY:
                return $factory->semiYearly($this->getRepeatAmount(), $this->getRepeatAmountExtended());
            case self::REPEAT_FREQUENCY_YEARLY:
                return $factory->yearly($this->getRepeatAmount(), $this->getRepeatAmountExtended());
            default:
                throw new LogicException(sprintf('Recurrence interval for %s is not yet supported.', $this->getRepeatFrequency()));
        }
    }

    public function shouldSendOn(DateValue $day): bool
    {
        if ($this->getIsTrashed()) {
            return false;
        }

        $last_trigger_on = $this->getLastTriggerOn();

        if ($last_trigger_on && $last_trigger_on->format('Y-m-d') === $day->format('Y-m-d')) {
            return false;
        }

        return $this->getRecurrenceInterval()->shouldRecurOnDay($day);
    }

    public function getNextTriggerOn(): ?DateValue
    {
        return $this->getRecurrenceInterval()->getNextRecurrence($this->getLastTriggerOn());
    }

    public function getCalendarFeedSummary(
        IUser $user,
        string $prefix = '',
        string $sufix = ''
    ): string
    {
        return sprintf(
            '%s ↻ %s: %s%s',
            $prefix,
            lang('Recurring Task', true, $user->getLanguage()),
            $this->getName(),
            $sufix
        );
    }
}
