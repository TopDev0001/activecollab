<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class AccountRetiredNotification extends Notification
{
    public function &setSubject($subject)
    {
        $this->setAdditionalProperty('subject', $subject);

        return $this;
    }

    public function getSubject()
    {
        return $this->getAdditionalProperty('subject');
    }

    public function getExportToEmailAddress()
    {
        return $this->getAdditionalProperty('export_to_email_address');
    }

    public function &setExportToEmailAddress(string $email_address)
    {
        $this->setAdditionalProperty('export_to_email_address', $email_address);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return array_merge(
            parent::getAdditionalTemplateVars($channel),
            [
                'export_to_email_address' => $this->getExportToEmailAddress(),
                'retired_account_id' => AngieApplication::getAccountId(),
            ]
        );
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true;
        } elseif ($channel instanceof WebInterfaceNotificationChannel) {
            return false;
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
