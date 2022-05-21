<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class BudgetNotificationMaintenanceJob extends MaintenanceJob
{
    public function __construct(array $data = null)
    {
        $data['command'] = 'budget_notifications_maintenance';
        if (!isset($data['ondemand'])) {
            $data['ondemand'] = true;
        }

        if (!isset($data['tasks_path'])) {
            $data['tasks_path'] = '';
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $instance_id = $this->getInstanceId();
        $logger = $this->getLogger();
        if ($this->getData('ondemand')) {
            $this->runActiveCollabCliCommand(
                $instance_id,
                $this->getData('command'),
                "Budget Notification Maintenance for account #{$instance_id} has started checking projects.",
                $logger
            );
        } else {
            $php_path = (new PhpExecutableFinder())->find();
            $tasks_path = $this->getData('tasks_path');
            if ($php_path && $tasks_path) {
                $process = new Process([
                    $php_path,
                    $tasks_path . DIRECTORY_SEPARATOR . 'activecollab-cli.php',
                    $this->getData('command')
                ]);
                $process->run();
            }
        }

        if ($logger) {
            $logger->info(
                'Budget notification runner at account #{account_id} has finished his job.',
                $this->getLogContextArguments(
                    [
                        'account_id' => $instance_id,
                    ]
                )
            );
        }
    }
}
