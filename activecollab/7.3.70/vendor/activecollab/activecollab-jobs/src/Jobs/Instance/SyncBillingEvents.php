<?php
/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class SyncBillingEvents extends MaintenanceJob
{
    public function execute()
    {
        $logger = $this->getLogger();

        $instance_id = $this->getData('instance_id');

        $command = new ExecuteActiveCollabCliCommand(
            [
                'instance_type' => 'feather',
                'instance_id' => $instance_id,
                'command' => 'ondemand:sync_billing_events',
            ]
        );

        if ($this->hasContainer()) {
            $command->setContainer($this->getContainer());
        }

        $command->execute();

        if ($logger) {
            $logger->info(
                "Sync Billing records is completed for account #{$instance_id}",
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
