<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Urls\PermalinkInterface;

class Stopwatch extends BaseStopwatch
{
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'parent_type' => $this->getParentType(),
            'parent_id' => $this->getParentId(),
            'user_id' => $this->getUserId(),
            'user_name' => $this->getUserName(),
            'user_email' => $this->getUserEmail(),
            'started_on' => $this->getStartedOn(),
            'is_kept' => $this->getIsKept(),
            'elapsed' => $this->getElapsed(),
            'updated_on' => $this->getUpdatedOn(),
            'created_on' => $this->getCreatedOn(),
            'project_id' => $this->getProjectId(),
            'notification_sent_at' => $this->getNotificationSentAt(),
        ];
    }

    public function getProjectId()
    {
        if ($this->getParentType() === Task::class) {
            return DB::executeFirstCell('SELECT `project_id` FROM `tasks` WHERE `id` = ?', $this->getParentId());
        } else {
            return $this->getParentId();
        }
    }

    public function whoCanSeeThis()
    {
        return [$this->getUserId()];
    }

    public function getViewUrl(): string
    {
        if ($this->getParent() instanceof PermalinkInterface){
            return $this->getParent()->getViewUrl();
        }

        return '';
    }

    /**
     * @return IUser|User
     */
    public function getUser()
    {
       return DataObjectPool::get(User::class, $this->getUserId());
    }
}
