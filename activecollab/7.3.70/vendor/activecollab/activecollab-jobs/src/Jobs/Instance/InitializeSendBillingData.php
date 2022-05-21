<?php
/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class InitializeSendBillingData extends MaintenanceJob
{
    public function execute()
    {
        $logger = $this->getLogger();

        $instance_id = $this->getData('instance_id');

        $command = new ExecuteActiveCollabCliCommand(
            [
                'instance_type' => 'feather',
                'instance_id' => $instance_id,
                'command' => 'ondemand:billing:send_billing_data',
            ]
        );

        if ($this->hasContainer()) {
            $command->setContainer($this->getContainer());
        }

        $command->execute();

        if ($logger) {
            $logger->info(
                "Initializing sending billing data is completed for account #{$instance_id}",
                $this->getLogContextArguments(
                    [
                        'account_id' => $instance_id,
                        'command' => $command,
                    ]
                )
            );
        }
    }
}
