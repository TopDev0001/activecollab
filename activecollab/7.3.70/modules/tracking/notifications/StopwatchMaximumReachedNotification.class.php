<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class StopwatchMaximumReachedNotification extends Notification
{
    public function &setDescription(string $description)
    {
        $this->setAdditionalProperty('description', $description);

        return $this;
    }

    public function &setUrl(string $url)
    {
        $this->setAdditionalProperty('url', $url);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return [
                'description' => $this->getAdditionalProperty('description'),
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
