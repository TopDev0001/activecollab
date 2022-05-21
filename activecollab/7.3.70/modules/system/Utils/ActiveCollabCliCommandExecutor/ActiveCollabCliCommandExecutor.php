<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ActiveCollabCliCommandExecutor;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\ExecuteActiveCollabCliCommand;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;

class ActiveCollabCliCommandExecutor implements ActiveCollabCliCommandExecutorInterface
{
    private JobsDispatcherInterface $dispatcher;
    private string $environment_path;
    private AccountIdResolverInterface $account_id_resolver;

    public function __construct(
        JobsDispatcherInterface $dispatcher,
        string $environment_path,
        AccountIdResolverInterface $account_id_resolver
    ) {
        $this->dispatcher = $dispatcher;
        $this->environment_path = $environment_path;
        $this->account_id_resolver = $account_id_resolver;
    }

    public function execute(
        string $command_name,
        array $command_arguments,
        string $channel,
        bool $async = true
    ): void
    {
        $command = $this->prepareCommand($command_name, $command_arguments);

        if ($async) {
            $this->dispatcher->dispatch(
                $command,
                $channel,
            );
        } else {
            $this->dispatcher->execute(
                $command
            );
        }
    }

    protected function prepareCommand(
        string $command_name,
        array $command_arguments
    ): ExecuteActiveCollabCliCommand
    {
        return new ExecuteActiveCollabCliCommand(
            [
                'instance_id' => $this->account_id_resolver->getAccountId(),
                'instance_type' => ActiveCollabCliCommandExecutorInterface::INSTANCE_TYPE_FEATHER,
                'tasks_path' => "{$this->environment_path}/tasks",
                'command' => $command_name,
                'command_arguments' => $command_arguments,
            ]
        );
    }
}
