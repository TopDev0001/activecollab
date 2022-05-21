<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use Angie\Notifications\PushNotificationInterface;

/**
 * New subtask notification.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage notifications
 */
class NewSubtaskNotification extends BaseSubtaskNotification implements PushNotificationInterface
{
    /**
     * Set update flags for combined object updates collection.
     */
    public function onObjectUpdateFlags(array &$updates)
    {
        $updates['new_subtask'][] = $this->getId();
    }

    public function optOutConfigurationOptions(NotificationChannel $channel = null): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return array_merge(parent::optOutConfigurationOptions($channel), ['notifications_user_send_email_assignments']);
        }

        return parent::optOutConfigurationOptions($channel);
    }
}
