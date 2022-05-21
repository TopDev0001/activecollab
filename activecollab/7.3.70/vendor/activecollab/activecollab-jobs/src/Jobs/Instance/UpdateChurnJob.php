<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class UpdateChurnJob extends ExecuteActiveCollabCliCommand
{
    public function __construct(array $data = null)
    {
        $data['instance_type'] = 'feather';

        parent::__construct(
            array_merge(
                $data,
                [
                    'command' => 'ondemand:account:update_churn_for_accounts',
                ]
            )
        );
    }
}
