<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class UnreadMessagesNotification extends Notification
{
    public function getTotal()
    {
        return $this->getAdditionalProperty('total');
    }

    public function &setTotal(int $total)
    {
        $this->setAdditionalProperty('total', $total);

        return $this;
    }

    public function getApplicationUrl()
    {
        return $this->getAdditionalProperty('application_url');
    }

    public function &setApplicationUrl(string $application_url)
    {
        $this->setAdditionalProperty('application_url', $application_url);

        return $this;
    }

    public function &setMessagesByConversation(array $messages_by_conversations)
    {
        $this->setAdditionalProperty('messages_by_conversations', $messages_by_conversations);

        return $this;
    }

    public function getMessagesByConversation()
    {
        return $this->getAdditionalProperty('messages_by_conversations');
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'messages_by_conversations' => $this->getMessagesByConversation(),
            'total' => $this->getTotal(),
            'application_url' => $this->getApplicationUrl(),
        ];
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

    public function getUnsubscribeUrl(IUser $user): string
    {
        return $this->getEmailSettingsUrl();
    }
}
