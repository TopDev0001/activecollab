<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

trait INewProjectElementNotificationOptOutConfig
{
    public function optOutConfigurationOptions(NotificationChannel $channel = null): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return array_merge(parent::optOutConfigurationOptions($channel), ['notifications_user_send_email_new_project_element']);
        }

        return parent::optOutConfigurationOptions($channel);
    }
}
