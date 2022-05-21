<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use Angie\Notifications\PushNotificationInterface;

/**
 * Task reassigned notification.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage notifications
 */
class TaskReassignedNotification extends Notification implements PushNotificationInterface
{
    public function onObjectUpdateFlags(array &$updates)
    {
        $updates['reassigned'][] = $this->getId();
    }

    public function onRelatedObjectsTypeIdsMap(array &$type_ids_map)
    {
        /** @var Task $parent */
        if ($parent = $this->getParent()) {
            if (empty($type_ids_map[Project::class])) {
                $type_ids_map[Project::class] = [$parent->getProjectId()];
            } else {
                if (!in_array($parent->getProjectId(), $type_ids_map[Project::class])) {
                    $type_ids_map[Project::class][] = $parent->getProjectId();
                }
            }
        }
    }

    public function optOutConfigurationOptions(NotificationChannel $channel = null): array
    {
        $result = parent::optOutConfigurationOptions($channel);

        if ($channel instanceof EmailNotificationChannel) {
            $result[] = 'notifications_user_send_email_assignments';
        }

        return $result;
    }
}
