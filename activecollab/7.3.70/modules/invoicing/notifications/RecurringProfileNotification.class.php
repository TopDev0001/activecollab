<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

abstract class RecurringProfileNotification extends Notification
{
    /**
     * Set parent profile.
     *
     * @return RecurringProfileNotification
     */
    public function &setProfile(RecurringProfile $profile)
    {
        $this->setAdditionalProperty('profile_id', $profile->getId());

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'profile' => $this->getProfile(),
        ];
    }

    /**
     * Return parent recurring profile.
     *
     * @return RecurringProfile|DataObject
     */
    public function getProfile()
    {
        return DataObjectPool::get(RecurringProfile::class, $this->getAdditionalProperty('profile_id'));
    }

    /**
     * Return files attached to this notification, if any.
     *
     * @return array
     */
    public function getAttachments(NotificationChannel $channel)
    {
        /** @var Invoice $parent */
        if ($parent = $this->getParent()) {
            return [$parent->exportToFile() => 'invoice.pdf'];
        }

        return null;
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
