<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class StorageOverusedNotification extends Notification
{
    /**
     * Get allowed disk space.
     *
     * @return mixed
     */
    public function getDiskSpaceLimit()
    {
        return $this->getAdditionalProperty('disk_space_limit');
    }

    /**
     * Set allowed disk space.
     *
     * @param  string                      $disk_space_limit
     * @return StorageOverusedNotification
     */
    public function &setDiskSpaceLimit($disk_space_limit)
    {
        $this->setAdditionalProperty('disk_space_limit', $disk_space_limit);

        return $this;
    }

    public function &setStorageAddOnsUrl($url)
    {
        $this->setAdditionalProperty('storage_add_ons_url', $url);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'disk_space_limit' => $this->getDiskSpaceLimit(),
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
