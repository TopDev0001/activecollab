<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class PersistWarehouseStoreId extends MaintenanceJob
{
    public function execute()
    {
        $logger = $this->getLogger();

        $instance_id = $this->getData('instance_id');

        $this->persistWarehouseStoreId($instance_id, $logger);

        if ($logger) {
            $logger->info(
                'Warehouse store ID for account #{account_id} have been persisted in Multi Account DB',
                $this->getLogContextArguments(
                    [
                        'account_id' => $instance_id,
                    ]
                )
            );
        }
    }
}
