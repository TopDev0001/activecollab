<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

class ResolveBalanceJob extends ExecuteActiveCollabCliCommand
{
    public function __construct(?array $data = null)
    {
        $command_arguments = [];

        if (!empty($data['date'])) {
            $command_arguments[] = $data['date'];
        }

        parent::__construct(
            array_merge(
                $data,
                [
                    'command' => 'ondemand:billing:account_balance:resolve_balance',
                    'command_arguments' => $command_arguments,
                    'command_options' => [],
                ]
            )
        );
    }
}
