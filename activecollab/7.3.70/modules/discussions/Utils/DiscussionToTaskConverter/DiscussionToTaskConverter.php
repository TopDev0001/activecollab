<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Discussions\Utils\DiscussionToTaskConverter;

use Angie\Inflector;
use Client;
use DB;
use Discussion;
use InstanceCreatedActivityLog;
use LogicException;
use NotificationRecipients;
use Notifications;
use Task;
use TaskLists;
use Tasks;
use User;

class DiscussionToTaskConverter implements DiscussionToTaskConverterInterface
{
    public function convertToTask(Discussion $discussion, User $by): Task
    {
        // users who can't add tasks to a project certainly can not promote discussions to tasks
        if (!Tasks::canAdd($by, $discussion->getProject())) {
            throw new LogicException('The user is not allowed to create tasks in this project');
        }

        // client plus member can add tasks but they cannot promote other members discussions to tasks
        if ($by instanceof Client && $discussion->getCreatedById() != $by->getId()) {
            throw new LogicException("The user is not allowed to promote other member's discussions to task");
        }

        $task = null;

        DB::transact(
            function () use ($discussion, $by, &$task) {
                if ($this->alreadyConvertedToTask($discussion)) {
                    throw new LogicException('This Discussion is already promoted to Task.');
                }

                $task_list = TaskLists::getFirstTaskList($discussion->getProject());

                $task = Tasks::create(
                    [
                        'project_id' => $discussion->getProjectId(),
                        'task_list_id' => $task_list->getId(),
                        'name' => $discussion->getName(),
                        'body' => $discussion->getBody(),
                        'created_on' => $discussion->getCreatedOn(),
                        'created_by_id' => $discussion->getCreatedById(),
                        'updated_on' => $discussion->getUpdatedOn(),
                        'updated_by_id' => $discussion->getUpdatedById(),
                        'is_hidden_from_clients' => $discussion->getIsHiddenFromClients(),
                    ]
                );

                // Update tasks table with discussion relationship id
                DB::execute(
                    'UPDATE `tasks` SET `created_from_discussion_id` = ? WHERE `id` = ?',
                    $discussion->getId(),
                    $task->getId()
                );

                $task->clearSubscribers(); // Clear default subscribers - we'll move everything from a discussion and make sure that $by is subscribed later on

                $notification_ids = DB::executeFirstColumn(
                    'SELECT `id` FROM `notifications` WHERE `parent_type` = ? AND `parent_id` = ?',
                    Discussion::class,
                    $discussion->getId()
                );

                // Remove all discussion notifications
                if (!empty($notification_ids)) {
                    NotificationRecipients::deleteBy($notification_ids);
                    DB::execute('DELETE FROM notifications WHERE id IN (?)', $notification_ids);

                    Notifications::clearCacheFor($notification_ids);
                }

                // Remove discussion creation log, that's the only log that we will not need (we need info about comments)
                DB::execute(
                    'DELETE FROM activity_logs WHERE type = ? AND parent_type = ? AND parent_id = ?',
                    InstanceCreatedActivityLog::class,
                    Discussion::class,
                    $discussion->getId()
                );

                // Move comments, attachments and activity logs to the new parent
                foreach (['activity_logs', 'attachments', 'comments', 'subscriptions'] as $table_name) {
                    $ids = DB::executeFirstColumn(
                        sprintf('SELECT `id` FROM %s WHERE `parent_type` = ? AND `parent_id` = ?', $table_name),
                        Discussion::class,
                        $discussion->getId()
                    );

                    if (!empty($ids)) {
                        DB::execute(
                            sprintf('UPDATE %s SET `parent_type` = ?, `parent_id` = ? WHERE `id` IN (?)', $table_name),
                            Task::class,
                            $task->getId(),
                            $ids
                        );

                        call_user_func(
                            [
                                Inflector::camelize($table_name),
                                'clearCacheFor',
                            ],
                            $ids
                        );
                    }
                }

                // Make sure that object path for activity logs is properly updated to task's path
                DB::execute(
                    'UPDATE activity_logs SET parent_path = ? WHERE parent_type = ? AND parent_id = ?',
                    $task->getObjectPath(),
                    Task::class,
                    $task->getId()
                );

                $task->subscribe($by);

                $discussion->delete();
            }
        );

        return $task;
    }

    private function alreadyConvertedToTask(Discussion $discussion): bool
    {
        return (bool) DB::executeFirstColumn(
            'SELECT `id` FROM `tasks` WHERE `created_from_discussion_id` = ?',
            $discussion->getId()
        );
    }
}
