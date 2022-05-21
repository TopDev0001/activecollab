<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use InvalidArgumentException;

class AsanaAttachmentsImport extends ExecuteActiveCollabCliCommand
{
    public function __construct(array $data = null)
    {
        if (empty($data['context_type'])) {
            throw new InvalidArgumentException("'context_type' property is required");
        }

        if (empty($data['context_id'])) {
            throw new InvalidArgumentException("'context_id' property is required");
        }

        if (empty($data['attachment_id'])) {
            throw new InvalidArgumentException("'attachment_id' property is required");
        }

        if (empty($data['mime_type'])) {
            throw new InvalidArgumentException("'mime_type' property is required");
        }

        if (empty($data['user_id'])) {
            throw new InvalidArgumentException("'user_id' property is required");
        }

        parent::__construct(
            array_merge(
                $data,
                [
                    'command' => 'import_asana_attachment',
                    'command_arguments' => [],
                    'command_options' => [
                        'context-type' => $data['context_type'],
                        'context-id' => $data['context_id'],
                        'attachment-id' => $data['attachment_id'],
                        'mime-type' => $data['mime_type'],
                        'user-id' => $data['user_id'],
                    ],
                ]
            )
        );
    }
}
