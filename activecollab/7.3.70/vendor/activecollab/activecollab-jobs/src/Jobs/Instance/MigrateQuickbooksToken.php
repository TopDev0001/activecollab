<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use Exception;

class MigrateQuickbooksToken extends MaintenanceJob
{
    public function execute()
    {
        $logger = $this->getLogger();

        $instance_id = $this->getData('instance_id');

        try {
            $this->migrateQuickbooksToken($instance_id, $logger);

            if ($logger) {
                $logger->info(
                    'Migrations for account #{account_id} have been ran',
                    $this->getLogContextArguments(
                        [
                            'account_id' => $instance_id,
                        ]
                    )
                );
            }
        } catch (Exception $e) {
            if ($logger) {
                $logger->error(
                    "Migrations for account #{account_id} failed with error '{error}'.",
                    $this->getLogContextArguments(
                        [
                            'account_id' => $instance_id,
                            'error' => $e->getMessage(),
                            'trace' => $e,
                        ]
                    )
                );
            }

            throw $e;
        }
    }
}
