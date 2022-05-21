<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

abstract class FwCustomReminderNotification extends Notification
{
    /**
     * Return reminder instance.
     *
     * @return Reminder|DataObject
     */
    public function getReminder()
    {
        return DataObjectPool::get(
            Reminder::class,
            $this->getAdditionalProperty('reminder_id')
        );
    }

    /**
     * Set reminder instance.
     *
     * @return CustomReminderNotification|$this
     */
    public function &setReminder(Reminder $reminder)
    {
        $this->setAdditionalProperty('reminder_id', $reminder->getId());

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'reminder' => $this->getReminder(),
        ];
    }

    public function ignoreSender(): bool
    {
        return false;
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof WebInterfaceNotificationChannel) {
            return true; // Always deliver this notification to web interface
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
