<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance\Webhooks;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\ExecuteActiveCollabCliCommand;
use InvalidArgumentException;

class ChangeWebhookPriorityJob extends ExecuteActiveCollabCliCommand
{
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
    ];

    public function __construct(array $data = null)
    {
        if (empty($data['webhook_id'])) {
            throw new InvalidArgumentException("'webhook_id' property is required");
        }

        if (empty($data['new_priority'])) {
            throw new InvalidArgumentException("'new_priority' property is required");
        } elseif (!in_array($data['new_priority'], self::PRIORITIES)) {
            throw new InvalidArgumentException("'new_priority' is not valid");
        }

        parent::__construct(
            array_merge(
                $data,
                [
                    'instance_type' => self::FEATHER,
                    'command' => 'webhook:change_webhook_priority',
                    'command_arguments' => [
                        $data['webhook_id'],
                        $data['new_priority'],
                    ],
                ]
            )
        );
    }
}
