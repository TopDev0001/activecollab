<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use Angie\Notifications\PushNotificationInterface;

class NewProjectNotification extends Notification implements PushNotificationInterface
{
    use INewInstanceUpdate;

    public function supportsGoToAction(IUser $recipient): bool
    {
        return true;
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
