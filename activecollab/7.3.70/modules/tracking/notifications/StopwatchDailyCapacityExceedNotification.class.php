<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class StopwatchDailyCapacityExceedNotification extends Notification
{
    public function &setDailyCapacity(float $daily_capacity): self
    {
        $this->setAdditionalProperty('daily_capacity', $daily_capacity);

        return $this;
    }

    public function &setUrl(string $url): self
    {
        $this->setAdditionalProperty('url', $url);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return [
                'daily_capacity' => $this->getAdditionalProperty('daily_capacity'),
                'url' => $this->getAdditionalProperty('url'),
            ];
        }

        return parent::getAdditionalProperties();
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
