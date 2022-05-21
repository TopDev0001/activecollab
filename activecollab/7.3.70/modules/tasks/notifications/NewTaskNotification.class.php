<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use Angie\Notifications\PushNotificationInterface;

class NewTaskNotification extends Notification implements PushNotificationInterface
{
    use INewInstanceUpdate;
    use INewProjectElementNotificationOptOutConfig;

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($recipient instanceof User) {
            $parent = $this->getParent();

            if ($parent instanceof Task) {
                if ($recipient instanceof Client && $parent->getIsHiddenFromClients()) {
                    return false;
                }

                // Override access to channel if recipient is assignee and has notifications_user_send_email_assignments set to true
                if ($parent->isAssignee($recipient) && ConfigOptions::getValueFor('notifications_user_send_email_assignments', $recipient)) {
                    return true;
                }
            }
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }

    public function isUserBlockingThisNotification(IUser $user, NotificationChannel $channel = null): bool
    {
        if ($user instanceof User && $channel instanceof EmailNotificationChannel) {
            $parent = $this->getParent();

            // Override notification blocking if recipient is assignee and has notifications_user_send_email_assignments set to true
            if ($parent instanceof Task && $parent->isAssignee($user) && ConfigOptions::getValueFor('notifications_user_send_email_assignments', $user)) {
                return false;
            }
        }

        return parent::isUserBlockingThisNotification($user, $channel);
    }

    public function supportsGoToAction(IUser $recipient): bool
    {
        return $recipient instanceof User && $this->getParent() && $this->getParent()->isAssignee($recipient);
    }

    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
        $task = $this->getParent();

        if ($task instanceof Task && (empty($type_ids_map[Project::class]) || !in_array($task->getProjectId(), $type_ids_map[Project::class]))) {
            $type_ids_map[Project::class][] = $task->getProjectId();
        }
    }
}
