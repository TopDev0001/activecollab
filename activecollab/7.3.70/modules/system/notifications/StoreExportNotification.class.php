<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class StoreExportNotification extends Notification
{
    /**
     * Get download url.
     *
     * @return mixed
     */
    public function getDownloadUrl()
    {
        return $this->getAdditionalProperty('download_url');
    }

    /**
     * Set download url.
     *
     * @param  string                  $download_url
     * @return StoreExportNotification
     */
    public function &setDownloadUrl($download_url)
    {
        $this->setAdditionalProperty('download_url', $download_url);

        return $this;
    }

    /**
     * Get export type.
     *
     * @return mixed
     */
    public function getExportType()
    {
        return $this->getAdditionalProperty('export_type');
    }

    /**
     * Set export type.
     *
     * @param  string                  $export_type
     * @return StoreExportNotification
     */
    public function &setExportType($export_type)
    {
        $this->setAdditionalProperty('export_type', $export_type);

        return $this;
    }

    /**
     * Get user name.
     *
     * @return mixed
     */
    public function getUserName()
    {
        return $this->getAdditionalProperty('user_name');
    }

    /**
     * Set user name.
     *
     * @param  string                  $user_name
     * @return StoreExportNotification
     */
    public function &setUserName($user_name)
    {
        $this->setAdditionalProperty('user_name', $user_name);

        return $this;
    }

    /**
     * Get export archive size.
     *
     * @return mixed
     */
    public function getArchiveSize()
    {
        return $this->getAdditionalProperty('archive_size');
    }

    /**
     * Set export archive size.
     *
     * @param  string                  $archive_size
     * @return StoreExportNotification
     */
    public function &setArchiveSize($archive_size)
    {
        $this->setAdditionalProperty('archive_size', $archive_size);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return array_merge(
            parent::getAdditionalTemplateVars($channel),
            [
                'user_name' => $this->getUserName(),
                'archive_size' => $this->getArchiveSize(),
                'export_type' => $this->getExportType(),
                'download_url' => $this->getDownloadUrl(),
                'account_id' => AngieApplication::getAccountId(),
            ]
        );
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
