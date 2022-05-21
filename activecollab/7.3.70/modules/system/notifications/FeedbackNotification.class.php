<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

/**
 * Feedback notification.
 *
 * @package ActiveCollab.modules.system
 * @subpackage notifications
 */
class FeedbackNotification extends Notification
{
    /**
     * Set comment provided by the user.
     *
     * @param  string               $comment
     * @return FeedbackNotification
     */
    public function &setComment($comment)
    {
        $this->setAdditionalProperty('comment', $comment);

        return $this;
    }

    /**
     * Set details collected by the system at the time when feedback is sent.
     *
     * @return FeedbackNotification
     */
    public function &setDetails(array $details)
    {
        $this->setAdditionalProperty('details', $details);

        return $this;
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array &$result)
    {
        $result['comment'] = $this->getComment();
        $result['details'] = $this->getDetails();
    }

    /**
     * Return comment provided by the user.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->getAdditionalProperty('comment');
    }

    /**
     * Return details.
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->getAdditionalProperty('details');
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof WebInterfaceNotificationChannel) {
            return false; // Never show in web interface
        }

        if ($channel instanceof EmailNotificationChannel) {
            return true; // Always send an email
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return [
                'comment' => $this->getComment(),
                'details' => $this->getDetails(),
            ];
        }

        return parent::getAdditionalTemplateVars($channel);
    }
}
