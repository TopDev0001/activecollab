<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Notifications\Channel\NotificationChannel;
use ActiveCollab\Foundation\Urls\Router\RouterInterface;

class ReplacingProjectUserNotification extends Notification
{
    /**
     * @var Task[]|null
     */
    private $open_tasks = false;

    /**
     * @var User
     */
    private $recipient = false;

    /**
     * Set replacing user.
     *
     * @return ReplacingProjectUserNotification
     */
    public function &setReplacingUser(User $user)
    {
        $this->setAdditionalProperty('replacing_user_id', $user->getId());

        return $this;
    }

    public function getAdditionalTemplateVars(NotificationChannel $channel): array
    {
        return [
            'replacing_user' => $this->getReplacingUser(),
            'recipient_tasks_url' => $this->getRecipient() instanceof User
                ? AngieApplication::getContainer()
                    ->get(RouterInterface::class)
                        ->assemble(
                            'user_tasks',
                            [
                                'user_id' => $this->getRecipient()->getId(),
                            ]
                        )
                : '#',
            'open_tasks' => $this->getOpenTasks(),
        ];
    }

    /**
     * @return User|DataObject
     */
    public function getReplacingUser()
    {
        return DataObjectPool::get(
            User::class,
            $this->getAdditionalProperty('replacing_user_id')
        );
    }

    /**
     * Return first notification recipient.
     *
     * @return User
     */
    private function getRecipient()
    {
        if ($this->recipient === false) {
            if ($recipients = $this->getRecipients()) {
                $this->recipient = first($recipients);
            }
        }

        return $this->recipient;
    }

    /**
     * Return a list of open tasks.
     *
     * @return Task[]|null
     */
    private function getOpenTasks()
    {
        if ($this->open_tasks === false) {
            $this->open_tasks = null;

            if ($recipient = $this->getRecipient()) {
                /** @var Project $project */
                if ($project = $this->getParent()) {
                    $this->open_tasks = Tasks::findBySQL(
                        'SELECT * FROM tasks WHERE project_id = ? AND assignee_id = ? AND completed_on IS NULL AND is_trashed = ? ORDER BY position',
                        $project->getId(),
                        $recipient->getId(),
                        false
                    );
                }
            }
        }

        return $this->open_tasks;
    }
}
