<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

/**
 * Base bounce email notification.
 *
 * @package angie.frameworks.email
 * @subpackage notifications
 */
abstract class FwBounceEmailNotification extends Notification
{
    /**
     * Return bounce reason.
     *
     * @return string
     */
    public function getBounceReason()
    {
        return $this->getAdditionalProperty('bounce_reason');
    }

    /**
     * Set bounce reason.
     *
     * @param  string                       $bounce_reason
     * @return RecurringProfileNotification
     */
    public function &setBounceReason($bounce_reason)
    {
        $this->setAdditionalProperty('bounce_reason', $bounce_reason);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'bounce_reason' => $this->getBounceReason(),
        ];
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true; // Always deliver this notification via email
        } elseif ($channel instanceof WebInterfaceNotificationChannel) {
            return false; // Never deliver this notification to web interface
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
