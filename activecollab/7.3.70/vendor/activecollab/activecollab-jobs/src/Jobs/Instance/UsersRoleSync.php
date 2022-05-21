<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class UsersRoleSync extends MaintenanceJob
{
    public function __construct(array $data = null)
    {
        $data['command'] = 'ondemand:user:sync_user_role';

        parent::__construct($data);
    }

    public function execute()
    {
        $instance_id = $this->getInstanceId();
        $logger = $this->getLogger();

        $this->runActiveCollabCliCommand(
            $instance_id,
            $this->getData('command'),
            "Users Role sync for account #{$instance_id} has been executed",
            $logger
        );

        if ($logger) {
            $logger->info(
                'Users Role sync for account #{account_id} has been executed',
                $this->getLogContextArguments(
                    [
                        'account_id' => $instance_id,
                    ]
                )
            );
        }
    }
}
