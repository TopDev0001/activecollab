<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class DisconnectFromServices extends MaintenanceJob
{
    public function execute()
    {
        $logger = $this->getLogger();

        $instance_id = $this->getData('instance_id');

        $this->disconnectFromServices($instance_id, $logger);

        if ($logger) {
            $logger->info(
                'Account #{account_id} has been disconnected from services',
                $this->getLogContextArguments(
                    [
                        'account_id' => $instance_id,
                    ]
                )
            );
        }
    }
}
