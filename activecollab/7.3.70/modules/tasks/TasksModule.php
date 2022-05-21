<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks;

use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\SubtaskEvents\SubtaskLifeCycleEventInterface;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskCompletedEventInterface;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskLifeCycleEventInterface;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskReopenedEventInterface;
use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskListEvents\TaskListLifeCycleEventInterface;
use AngieApplication;
use AngieModule;
use AssignmentsAsCalendarEventsCollection;
use AssignmentsCollection;
use BaseSubtaskNotification;
use CompletedParentTaskDependencyNotification;
use DataObjectPool;
use ISubtasks;
use ISubtasksImplementation;
use ITaskDependencies;
use ITaskDependenciesImplementation;
use NewSubtaskNotification;
use NewTaskNotification;
use OpenAssignmentsForAssigneeCollection;
use OpenAssignmentsForTeamCollection;
use ProjectRecurringTasksCollection;
use ProjectTasksCollection;
use ProjectTasksRawCollection;
use RecurringTask;
use RecurringTasks;
use Subtask;
use SubtaskActivityLog;
use SubtaskCreatedActivityLog;
use SubtaskReassignedNotification;
use Subtasks;
use SubtaskUpdatedActivityLog;
use Task;
use TaskDependenciesCollection;
use TaskDependenciesSuggestionsCollection;
use TaskLabel;
use TaskLabelInterface;
use TaskList;
use TaskLists;
use TaskReassignedNotification;
use Tasks;
use TaskSearchDocument;
use WorkloadTasksCollection;

class TasksModule extends AngieModule
{
    const NAME = 'tasks';

    protected string $name = 'tasks';
    protected string $version = '5.0';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            TaskList::class,
            function (array $ids): ?iterable
            {
                return TaskLists::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            Task::class,
            function (array $ids): ?iterable
            {
                return Tasks::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            Subtask::class,
            function (array $ids): ?iterable
            {
                return Subtasks::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            RecurringTask::class,
            function (array $ids): ?iterable
            {
                return RecurringTasks::findByIds($ids);
            }
        );
    }

    /**
     * Define module classes.
     */
    public function defineClasses()
    {
        require_once __DIR__ . '/resources/autoload_model.php';

        AngieApplication::setForAutoload(
            [
                ProjectTasksCollection::class => __DIR__ . '/models/ProjectTasksCollection.php',
                ProjectTasksRawCollection::class => __DIR__ . '/models/ProjectTasksRawCollection.php',
                WorkloadTasksCollection::class => __DIR__ . '/models/WorkloadTasksCollection.php',
                ProjectRecurringTasksCollection::class => __DIR__ . '/models/ProjectRecurringTasksCollection.php',

                TaskLabelInterface::class => __DIR__ . '/models/TaskLabelInterface.php',
                TaskLabel::class => __DIR__ . '/models/TaskLabel.php',

                AssignmentsCollection::class => __DIR__ . '/models/assignment_collections/AssignmentsCollection.class.php',
                OpenAssignmentsForAssigneeCollection::class => __DIR__ . '/models/assignment_collections/OpenAssignmentsForAssigneeCollection.class.php',
                OpenAssignmentsForTeamCollection::class => __DIR__ . '/models/assignment_collections/OpenAssignmentsForTeamCollection.class.php',
                AssignmentsAsCalendarEventsCollection::class => __DIR__ . '/models/assignment_collections/AssignmentsAsCalendarEventsCollection.class.php',

                NewTaskNotification::class => __DIR__ . '/notifications/NewTaskNotification.class.php',
                TaskReassignedNotification::class => __DIR__ . '/notifications/TaskReassignedNotification.class.php',

                BaseSubtaskNotification::class => __DIR__ . '/notifications/BaseSubtaskNotification.class.php',
                NewSubtaskNotification::class => __DIR__ . '/notifications/NewSubtaskNotification.class.php',

                // ---------------------------------------------------
                //  Subtasks
                // ---------------------------------------------------

                SubtaskActivityLog::class => __DIR__ . '/models/activity_logs/SubtaskActivityLog.class.php',
                SubtaskCreatedActivityLog::class => __DIR__ . '/models/activity_logs/SubtaskCreatedActivityLog.class.php',
                SubtaskUpdatedActivityLog::class => __DIR__ . '/models/activity_logs/SubtaskUpdatedActivityLog.class.php',

                // Notifications
                SubtaskReassignedNotification::class => __DIR__ . '/notifications/SubtaskReassignedNotification.class.php',

                ISubtasks::class => __DIR__ . '/models/ISubtasks.class.php',
                ISubtasksImplementation::class => __DIR__ . '/models/ISubtasksImplementation.class.php',

                ITaskDependencies::class => __DIR__ . '/models/ITaskDependencies.php',
                ITaskDependenciesImplementation::class => __DIR__ . '/models/ITaskDependenciesImplementation.php',

                TaskSearchDocument::class => __DIR__ . '/models/TaskSearchDocument.php',

                TaskDependenciesSuggestionsCollection::class => __DIR__ . '/models/TaskDependenciesSuggestionsCollection.php',
                TaskDependenciesCollection::class => __DIR__ . '/models/TaskDependenciesCollection.php',

                CompletedParentTaskDependencyNotification::class => __DIR__ . '/notifications/CompletedParentTaskDependencyNotification.class.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_daily_maintenance');
        $this->listen('on_all_indices');
        $this->listen('on_rebuild_activity_logs');
        $this->listen('on_rebuild_all_indices');
        $this->listen('on_object_from_notification_context');
        $this->listen('on_history_field_renderers');

        $this->listen('on_trash_sections');
        $this->listen('on_user_access_search_filter');

        $this->listen('on_notification_inspector');

        $this->listen('on_protected_config_options');
        $this->listen('on_initial_settings');
        $this->listen('on_resets_initial_settings_timestamp');

        $this->listen('on_reset_manager_states');
        $this->listen('on_email_received');
        $this->listen('on_task_updated');
        $this->listen('on_extra_stats');
    }

    public function defineListeners(): array
    {
        return [
            TaskLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch(
                    $event,
                    $event->getWebhookEventType(),
                    true
                );
            },
            TaskCompletedEventInterface::class => function ($event) {
                AngieApplication::taskDependencyNotificationDispatcher()->dispatchCompletedNotifications($event->getObject());
            },
            TaskReopenedEventInterface::class => function ($event) {
                AngieApplication::taskDependencyNotificationDispatcher()->removeCompletedNotifications($event->getObject());
            },
            TaskListLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            SubtaskLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
        ];
    }
}
