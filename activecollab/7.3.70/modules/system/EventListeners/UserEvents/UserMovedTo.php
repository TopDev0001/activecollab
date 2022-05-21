<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\UserEvents;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\ExecuteActiveCollabCliCommand;
use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use RealTimeIntegrationInterface;
use User;

abstract class UserMovedTo implements EventInterface
{
    protected JobsDispatcherInterface $dispatcher;
    protected int $account_id;

    public function __construct(JobsDispatcherInterface $dispatcher, int $account_id)
    {
        $this->dispatcher = $dispatcher;
        $this->account_id = $account_id;
    }

    protected function reassignUserConversationsAdmins(User $user)
    {
        $this->dispatcher->dispatch(
            new ExecuteActiveCollabCliCommand(
                [
                    'instance_id' => $this->account_id,
                    'instance_type' => 'feather',
                    'tasks_path' => ENVIRONMENT_PATH . '/tasks',
                    'command' => 'user:conversations_last_admin',
                    'command_arguments' => [
                        $user->getId(),
                    ],
                ]
            ),
            RealTimeIntegrationInterface::CHAT_JOBS_QUEUE_CHANNEL
        );
    }
}
