<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class CustomReminderNotification extends FwCustomReminderNotification
{
    public function onObjectUpdateFlags(array &$updates)
    {
        $updates['reminder'][] = $this->getId();
    }

    public function optOutConfigurationOptions(NotificationChannel $channel = null): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return array_merge(parent::optOutConfigurationOptions($channel), ['notifications_user_send_email_reminders']);
        }

        return parent::optOutConfigurationOptions($channel);
    }
}
