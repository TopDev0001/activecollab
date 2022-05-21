<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class NotifyOwnersNotification extends Notification
{
    /**
     * Return notification message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getAdditionalProperty('message');
    }

    /**
     * Set notification message.
     *
     * @param  string $message
     * @return $this
     */
    public function &setMessage($message)
    {
        $this->setAdditionalProperty('message', $message);

        return $this;
    }

    /**
     * Get notification additional payload.
     *
     * @return mixed
     */
    public function getAdditionalPayload()
    {
        return $this->getAdditionalProperty('additional_payload');
    }

    /**
     * Set notification additional payload.
     *
     * @param  null  $additional_payload
     * @return $this
     */
    public function &setAdditionalPayload($additional_payload = null)
    {
        if ($additional_payload) {
            $this->setAdditionalProperty('additional_payload', $additional_payload);
        }

        return $this;
    }

    /**
     * Get notification subject.
     *
     * @return mixed
     */
    public function getSubject()
    {
        return $this->getAdditionalProperty('subject');
    }

    /**
     * Set notification subject.
     *
     * @param  null              $subject
     * @return $this
     * @throws InvalidParamError
     */
    public function &setSubject($subject = null)
    {
        if ($subject) {
            $this->setAdditionalProperty('subject', $subject);

            return $this;
        } else {
            throw new InvalidParamError('subject', $subject);
        }
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'subject' => $this->getSubject(),
            'message' => $this->getMessage(),
            'additional_payload' => $this->getAdditionalPayload(),
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
