<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use InvalidArgumentException;

class SetConfigValueJob extends ExecuteActiveCollabCliCommand
{
    public function __construct(array $data = null)
    {
        $data['instance_type'] = 'feather';

        if (empty($data['config_option_name'])) {
            throw new InvalidArgumentException("'config_option_name' property is required");
        }

        if (!array_key_exists('config_option_value', $data)) {
            throw new InvalidArgumentException("'config_option_value' property is required");
        }

        if (empty($data['config_option_value_cast'])) {
            throw new InvalidArgumentException("'config_option_value_cast' property is required");
        }

        parent::__construct(
            array_merge(
                $data,
                [
                    'command' => 'config:set_value',
                    'command_arguments' => [
                        $data['config_option_name'],
                        $data['config_option_value'],
                    ],
                    'command_options' => [
                        'cast-to' => $data['config_option_value_cast']
                    ]
                ]
            )
        );
    }
}
