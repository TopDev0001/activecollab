<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class TestNotification extends Notification
{
    public function isUserMentioned(IUser $user): bool
    {
        return $user->getEmail() == 'email@a51dev.com' || parent::isUserMentioned($user);
    }

    public function optOutConfigurationOptions(NotificationChannel $channel = null): array
    {
        return array_merge(
            parent::optOutConfigurationOptions($channel),
            [
                'notification_accept_test_1',
                'notification_accept_test_2',
            ]
        );
    }
}
