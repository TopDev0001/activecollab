<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Notifications;

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Foundation\Notifications\NotificationInterface;
use Angie\Mailer\Decorator\Decorator;
use ApplicationObject;
use IUser;
use Notification;

interface NotificationsInterface
{
    public function notifyAbout(
        string $event,
        ApplicationObject $context = null,
        IUser $sender = null,
        Decorator $decorator = null
    ): NotificationInterface;

    public function getNotificationTemplatePath(Notification $notification, NotificationChannel $channel): string;

    /**
     * Send $notification to the list of recipients.
     *
     * @param IUser[] $users
     */
    public function sendNotificationToRecipients(
        Notification &$notification,
        $users,
        bool $skip_sending_queue = false
    ): void;

    /**
     * Return notification channels.
     *
     * @return NotificationChannel[]
     */
    public function getChannels();

    /**
     * Returns true if channels are open.
     *
     * @return bool
     */
    public function channelsAreOpen();

    /**
     * Open notifications channels for bulk sending.
     */
    public function openChannels();

    /**
     * Close notification channels for bulk sending.
     *
     * @param bool $sending_interupted
     */
    public function closeChannels($sending_interupted = false);
}
