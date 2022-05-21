<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

/**
 * Invoice reminder notification.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage notifications
 */
class InvoiceReminderNotification extends InvoiceNotification
{
    /**
     * Set reminder message.
     *
     * @param  string                      $value
     * @return InvoiceReminderNotification
     */
    public function &setReminderMessage($value)
    {
        $this->setAdditionalProperty('reminder_message', $value);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return array_merge(
            parent::getAdditionalTemplateVars($channel),
            [
                'additional_message' => $this->getReminderMessage(),
                'overdue_days' => $this->getParent()->getDueOn()->daysBetween(DateTimeValue::now()),
            ]
        );
    }

    /**
     * Get reminder message.
     *
     * @return string
     */
    public function getReminderMessage()
    {
        return $this->getAdditionalProperty('reminder_message');
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true; // Always deliver notifications via email
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
