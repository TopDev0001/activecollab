<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use Angie\Notifications\PushNotificationInterface;

class BudgetThresholdReachedNotification extends Notification implements PushNotificationInterface
{
    public function &setProjectName(string $projectName): self
    {
        $this->setAdditionalProperty('projectName', $projectName);

        return $this;
    }

    public function &setProjectUrl(string $projectUrl): self
    {
        $this->setAdditionalProperty('projectUrl', $projectUrl . '?open-project-info');

        return $this;
    }

    public function &setThreshold(int $threshold): self
    {
        $this->setAdditionalProperty('threshold', $threshold);

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        if ($channel instanceof EmailNotificationChannel) {
            return [
                'projectName' => $this->getAdditionalProperty('projectName'),
                'projectUrl' => $this->getAdditionalProperty('projectUrl'),
                'threshold' => $this->getAdditionalProperty('threshold'),
            ];
        }

        return parent::getAdditionalProperties();
    }

    /**
     * Set update flags for combined object updates collection.
     */
    public function onObjectUpdateFlags(array &$updates)
    {
        $updates['budget_alert'][] = $this->getId();
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true;
        }

        return parent::isThisNotificationVisibleInChannel($channel, $recipient);
    }
}
