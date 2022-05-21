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

class DisableWebhookJob extends ExecuteActiveCollabCliCommand
{
    public function __construct(array $data = null)
    {
        if (empty($data['webhook_id'])) {
            throw new InvalidArgumentException("'webhook_id' property is required");
        }

        parent::__construct(
            array_merge(
                $data,
                [
                    'instance_type' => self::FEATHER,
                    'command' => 'webhook:disable_webhook',
                    'command_arguments' => [
                        $data['webhook_id'],
                    ],
                    'command_options' => [
                        'notify-creator' => true,
                    ],
                ]
            )
        );
    }
}
