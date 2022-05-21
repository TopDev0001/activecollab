<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskFromRecurringTaskProducer;

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory\VariableProcessorFactoryInterface;
use Angie\Notifications\NotificationsInterface;
use DateValue;
use DB;
use Exception;
use IUser;
use NewTaskNotification;
use Psr\Log\LoggerInterface;
use RecurringTask;
use RuntimeException;
use Subtasks;
use Task;
use TaskList;
use TaskLists;
use Tasks;
use User;

class TaskFromRecurringTaskProducer implements TaskFromRecurringTaskProducerInterface
{
    private VariableProcessorFactoryInterface $variable_processor_factory;
    private NotificationsInterface $notifications;
    private LoggerInterface $logger;

    public function __construct(
        VariableProcessorFactoryInterface $variable_processor_factory,
        NotificationsInterface $notifications,
        LoggerInterface $logger
    )
    {
        $this->variable_processor_factory = $variable_processor_factory;
        $this->notifications = $notifications;
        $this->logger = $logger;
    }

    public function produceManually(
        RecurringTask $recurring_task,
        User $created_by,
        DateValue $trigger_date,
        string $override_name = null
    ): Task
    {
        return $this->produce(
            $recurring_task,
            $created_by,
            $trigger_date,
            true,
            [
                'name' => $override_name,
            ]
        );
    }

    public function produceAutomatically(
        RecurringTask $recurring_task,
        DateValue $trigger_date
    ): Task
    {
        return $this->produce(
            $recurring_task,
            $recurring_task->getCreatedBy(),
            $trigger_date,
            false
        );
    }

    private function produce(
        RecurringTask $recurring_task,
        IUser $created_by,
        DateValue $trigger_date,
        bool $created_manually,
        array $override_attributes = []
    ): Task
    {
        $variable_processor = $this->variable_processor_factory->createForTask();

        try {
            DB::beginWork('Begin: create task from recurring task @ ' . __CLASS__);

            [
                $start_on,
                $due_on
            ] = $this->getStartAndDueDate($recurring_task, $trigger_date);

            $attributes = array_merge(
                [
                    'project_id' => $recurring_task->getProjectId(),
                    'task_list_id' => $this->getTaskList($recurring_task)->getId(),
                    'assignee_id' => $recurring_task->getAssigneeId(),
                    'body_mode' => $recurring_task->getBodyMode(),
                    'is_important' => $recurring_task->getIsImportant(),
                    'created_by_id' => $created_by->getId(),
                    'created_by_name' => $created_by->getDisplayName(),
                    'created_by_email' => $created_by->getEmail(),
                    'start_on' => $start_on,
                    'due_on' => $due_on,
                    'job_type_id' => $recurring_task->getJobTypeId(),
                    'estimate' => $recurring_task->getEstimate(),
                    'is_hidden_from_clients' => $recurring_task->getIsHiddenFromClients(),
                    'created_from_recurring_task_id' => $recurring_task->getId(),
                ],
                $this->prepareAttributeOverride(
                    $override_attributes,
                    $recurring_task,
                    $variable_processor,
                    'name',
                    'body'
                )
            );

            $task = Tasks::create($attributes);

            $this->createSubtasksFromRecurringTask($task, $recurring_task->getSubtasks(), $created_by);

            $recurring_task->registerRecurringTaskCreated($task, $trigger_date);

            $recurring_task->cloneLabelsTo($task);
            $recurring_task->cloneAttachmentsTo($task);
            $recurring_task->cloneSubscribersTo($task, $recurring_task->getSubscriberIds());

            DB::commit('Done: create task & subtasks from recurring task @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: create task from recurring task @ ' . __CLASS__);
            throw $e;
        }

        /** @var NewTaskNotification $notification */
        $notification = $this->notifications->notifyAbout('tasks/new_task', $task, $created_by);
        $notification->sendToSubscribers();

        $this->logger->info(
            'Task #{task_id} has been created from {repeat_frequency} recurring task #{recurring_task_id}.',
            [
                'event' => 'task_created_from_recurring_task',
                'task_id' => $task->getId(),
                'recurring_task_id' => $recurring_task->getId(),
                'repeat_frequency' => $recurring_task->getRepeatFrequency(),
                'manually_triggered_by' => $created_manually ? $task->getCreatedById() : null,
            ]
        );

        return $task;
    }

    private function prepareAttributeOverride(
        array $override_attributes,
        RecurringTask $recurring_task,
        VariableProcessorInterface $variable_processor,
        string ...$attributes
    ): array
    {
        foreach ($attributes as $attribute) {
            if (empty($override_attributes[$attribute])) {
                if ($recurring_task->getFieldValue($attribute) !== null) {
                    $override_attributes[$attribute] = $variable_processor->process(
                        $recurring_task->getFieldValue($attribute)
                    );
                }
            } else {
                $override_attributes[$attribute] = $variable_processor->process($override_attributes[$attribute]);
            }
        }

        return $override_attributes;
    }

    /**
     * Return start and due date.
     *
     * @return DateValue[]
     */
    private function getStartAndDueDate(
        RecurringTask $recurring_task,
        DateValue $trigger_date
    ): array
    {
        $start_in = $recurring_task->getStartIn();
        $due_in = $recurring_task->getDueIn();

        if ($trigger_date) {
            $trigger_date_timestamp = $trigger_date->getTimestamp();
        } else {
            $trigger_date_timestamp = DateValue::now()->getTimestamp();
        }

//        if ($this->shouldSkipDaysOff($recurring_task)) {
//            $range = $this->getStartDueOnRangeSkipWeekend($trigger_date_timestamp);
//
//            $start_in = $range['start_in'];
//            $due_in = $range['due_in'];
//        }

        $start_on = null;
        $due_on = null;

        if (!empty($start_in) || !empty($due_in)) {
            $start_on = DateValue::makeFromTimestamp(strtotime('+' . $start_in . 'day', $trigger_date_timestamp));
        }

        if (!empty($due_in)) {
            $due_on = DateValue::makeFromTimestamp(strtotime('+' . $due_in . 'day', $trigger_date_timestamp));
        }

        if ($due_in === 0) {
            $start_on = $trigger_date;
            $due_on = $trigger_date;
        }

        return [
            $start_on,
            $due_on,
        ];
    }

    private function getTaskList(RecurringTask $recurring_task): TaskList
    {
        /** @var TaskList $task_list */
        $task_list = TaskLists::findById($recurring_task->getTaskListId());

        if (empty($task_list) || $task_list->isCompleted() || $task_list->getIsTrashed()) {
            $project = $recurring_task->getProject();

            if ($project) {
                $task_list = TaskLists::getFirstTaskList($project, false);

                if (empty($task_list)) {
                    throw new RuntimeException(
                        sprintf(
                            'Failed to locate task list for project #%d.',
                            $project->getId()
                        )
                    );
                }
            } else {
                throw new RuntimeException(
                    sprintf(
                        'Failed to locate project for recurring task #%d.',
                        $recurring_task->getId()
                    )
                );
            }
        }

        return $task_list;
    }

    private function createSubtasksFromRecurringTask(Task $task, ?iterable $subtasks, IUser $created_by): void
    {
        if (!empty($subtasks)) {
            foreach ($subtasks as $subtask) {
                $assignee_id = !empty($subtask['assignee_id']) ? (int) $subtask['assignee_id'] : null;

                Subtasks::create(
                    [
                        'task_id' => $task->getId(),
                        'assignee_id' => $assignee_id,
                        'body' => $subtask['body'],
                        'created_by_id' => $created_by->getId(),
                        'created_by_name' => $created_by->getDisplayName(),
                        'created_by_email' => $created_by->getEmail(),
                        'notify_assignee' => $this->shouldNotifySubtaskAssignee($assignee_id, $created_by),
                    ]
                );
            }
        }
    }

    private function shouldNotifySubtaskAssignee(?int $assignee_id, IUser $created_by): bool
    {
        return $assignee_id
            && $created_by instanceof User
            && $assignee_id != $created_by->getId();
    }

//    private function shouldSkipDaysOff(RecurringTask $recurring_task): bool
//    {
//        return $recurring_task->getRepeatFrequency() == RecurringTask::REPEAT_FREQUENCY_DAILY
//            && $recurring_task->getRepeatAmount() == 0;
//    }
}
