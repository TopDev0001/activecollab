<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation\EmailNotifications;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\ExecuteActiveCollabCliCommand;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Logger\LoggerInterface;
use DateTimeValue;
use RealTimeIntegrationInterface;
use Throwable;

class ConversationEmailNotificationsUpdater implements ConversationEmailNotificationsUpdaterInterface
{
    private UsersToNotifyAboutUnreadMessagesResolverInterface $users_resolver;
    private JobsDispatcherInterface $dispatcher;
    private string $environment_path;
    private AccountIdResolverInterface $account_id_resolver;
    private LoggerInterface $logger;

    public function __construct(
        UsersToNotifyAboutUnreadMessagesResolverInterface $users_resolver,
        JobsDispatcherInterface $dispatcher,
        string $environment_path,
        AccountIdResolverInterface $account_id_resolver,
        LoggerInterface $logger
    ) {
        $this->users_resolver = $users_resolver;
        $this->dispatcher = $dispatcher;
        $this->environment_path = $environment_path;
        $this->account_id_resolver = $account_id_resolver;
        $this->logger = $logger;
    }

    public function update(DateTimeValue $current_time): void
    {
        try {
            $account_id = $this->account_id_resolver->getAccountId();
            foreach ($this->users_resolver->getUserIds($current_time) as $id) {
                $this->dispatcher->dispatch(
                    new ExecuteActiveCollabCliCommand(
                        [
                            'instance_id' => $account_id,
                            'instance_type' => 'feather',
                            'tasks_path' => "{$this->environment_path}/tasks",
                            'command' => 'user:notify_user_about_unread_messages',
                            'command_arguments' => [$id],
                        ]
                    ),
                    RealTimeIntegrationInterface::CHAT_JOBS_QUEUE_CHANNEL
                );
            }
        } catch (Throwable $exception) {
            $this->logger->error(
                'Failed to notify users about unread chat messages.',
                ['exception' => $exception->getMessage()]
            );
        }
    }
}
