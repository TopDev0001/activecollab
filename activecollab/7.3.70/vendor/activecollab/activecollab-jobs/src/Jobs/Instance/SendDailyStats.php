<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use InvalidArgumentException;

class SendDailyStats extends ExecuteActiveCollabCliCommand
{
    public function __construct(array $data = null)
    {
        $data['instance_type'] = 'feather';

        if (empty($data['date'])) {
            throw new InvalidArgumentException('Date parameter is required.');
        }

        if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $data['date'])) {
            throw new InvalidArgumentException('Date parameter needs to be in YYYY-MM-DD format.');
        }

        parent::__construct(
            array_merge(
                $data,
                [
                    'command' => 'ondemand:stats',
                    'command_arguments' => [
                        $data['date'],
                    ],
                    'command_options' => [
                        'target' => 'shepherd',
                    ],
                ]
            )
        );
    }
}
