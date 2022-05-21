<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;

class MailToProjectReceivedNotification extends Notification
{
    public function getMatchedRecipientAddress(): string
    {
        return (string) $this->getAdditionalProperty('matched_recipient_address');
    }

    public function setMatchedRecipientAddress(string $matched_recipient_address): self
    {
        $this->setAdditionalProperty('matched_recipient_address', $matched_recipient_address);

        return $this;
    }

    public function getProject(): ?Project
    {
        return DataObjectPool::get(Project::class, $this->getAdditionalProperty('project_id'));
    }

    public function setProject(Project $project): self
    {
        $this->setAdditionalProperty('project_id', $project->getId());

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return array_merge(
            parent::getAdditionalTemplateVars($channel),
            [
                'project' => $this->getProject(),
                'matched_recipient_address' => $this->getMatchedRecipientAddress(),
            ]
        );
    }

    public function isThisNotificationVisibleInChannel(NotificationChannel $channel, IUser $recipient): bool
    {
        if ($channel instanceof EmailNotificationChannel) {
            return true;
        }

        return false;
    }
}
