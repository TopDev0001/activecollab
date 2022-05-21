<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

/**
 * Notify email sender notification.
 *
 * @package angie.frameworks.email
 * @subpackage notifications
 */
abstract class FwNotifyEmailSenderNotification extends Notification
{
    /**
     * Return email address.
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->getAdditionalProperty('email_address');
    }

    /**
     * Set email address.
     *
     * @param  string                        $email_address
     * @return NotifyEmailSenderNotification
     */
    public function &setEmailAddress($email_address)
    {
        $this->setAdditionalProperty('email_address', $email_address);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'email_address' => $this->getEmailAddress(),
        ];
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof WebInterfaceNotificationChannel) {
            return false;
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
