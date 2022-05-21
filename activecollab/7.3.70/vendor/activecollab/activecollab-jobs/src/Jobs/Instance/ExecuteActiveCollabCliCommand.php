<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class ExecuteActiveCollabCliCommand extends ExecuteCliCommand
{
    public function execute()
    {
        return $this->runCommand(
            sprintf(
                "env ACTIVECOLLAB_ACCOUNT_ID=%d php /var/www/activecollab-multi-account/tasks/activecollab-cli.php %s",
                $this->getInstanceId(),
                $this->prepareCommandFromData()
            ),
            '/var/www/activecollab-multi-account'
        );
    }
}
