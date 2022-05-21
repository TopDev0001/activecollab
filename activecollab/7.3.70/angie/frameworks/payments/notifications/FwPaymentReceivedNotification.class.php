<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

abstract class FwPaymentReceivedNotification extends Notification
{
    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'parent' => $this->getParent(),
        ];
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true; // Comment notifiactions should always go to email
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
