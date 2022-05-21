<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use InvalidArgumentException;

class SendUserBadgeCount extends ExecuteActiveCollabCliCommand
{
    public function __construct(array $data = null)
    {
        $data['instance_type'] = 'feather';

        if (empty($data['user_id'])) {
            throw new InvalidArgumentException('user_id parameter is required.');
        }

        parent::__construct(
            array_merge(
                $data,
                [
                    'command' => 'user:send_badge_count',
                    'command_arguments' => [
                        $data['user_id'],
                    ],
                ]
            )
        );
    }
}
