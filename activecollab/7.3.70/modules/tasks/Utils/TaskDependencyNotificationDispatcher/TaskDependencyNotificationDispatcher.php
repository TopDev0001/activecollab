<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskDependencyNotificationDispatcher;

use CompletedParentTaskDependencyNotification;
use LogicException;
use Task;

class TaskDependencyNotificationDispatcher implements TaskDependencyNotificationDispatcherInterface
{
    private $angie_notification_resolver;
    private $parent_notification_cleaner;

    public function __construct(
        callable $angie_notification_resolver,
        callable $parent_notification_cleaner
    )
    {
        $this->angie_notification_resolver = $angie_notification_resolver;
        $this->parent_notification_cleaner = $parent_notification_cleaner;
    }

    public function dispatchCompletedNotifications(Task $task): void
    {
        if (!$task->isCompleted()) {
            throw new LogicException(
                'Expect completed task for send completed parent task notifications.'
            );
        }

        /** @var Task $child_task */
        foreach ($this->getChildTasksToNotifyBy($task) as $child_task) {
            $notification = call_user_func(
                $this->angie_notification_resolver,
                'tasks/completed_parent_task_dependency',
                $child_task,
                $task->getCompletedBy()
            );

            if ($notification instanceof CompletedParentTaskDependencyNotification) {
                $notification
                    ->setCompletedParent($task)
                    ->sendToUsers($child_task->getAssignee());
            }
        }
    }

    private function getChildTasksToNotifyBy(Task $parent_task): array
    {
        $result = [];

        /** @var Task[] $children */
        if ($children = $parent_task->getChildDependencies()) {
            foreach ($children as $child) {
                if ($this->shouldSendForChildTask($parent_task, $child)) {
                    array_push($result, $child);
                }
            }
        }

        return $result;
    }

    private function shouldSendForChildTask(Task $parent, Task $child): bool
    {
        if (!$child->getAssignee() || $child->isCompleted()) {
            return false;
        }

        if ($assignee = $child->getAssignee()) {
            if ($assignee->isClient() && $parent->getIsHiddenFromClients()) {
                return false;
            }

            if ($child->getAssigneeId() === $parent->getCompletedById()) {
                return false;
            }
        }

        return true;
    }

    public function removeCompletedNotifications(Task $task): void
    {
        if ($children = $task->getChildDependencies()) {
            foreach ($children as $child) {
                call_user_func(
                    $this->parent_notification_cleaner,
                    $child,
                    'completed_parent_id',
                    $task->getId()
                );
            }
        }
    }
}
