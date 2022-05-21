<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class RebuildSearchIndex extends MaintenanceJob
{
    public function execute()
    {
        $logger = $this->getLogger();

        $instance_id = $this->getData('instance_id');

        $this->rebuildSearchIndex($instance_id, $logger);

        if ($logger) {
            $logger->info(
                'Search index for #{account_id} has been successfully rebuilt',
                $this->getLogContextArguments(
                    [
                        'account_id' => $instance_id,
                    ]
                )
            );
        }
    }
}
