<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use ActiveCollab\ActiveCollabJobs\ExecuteCliCommand\ExecuteCliCommandTrait;

abstract class ExecuteCliCommand extends Job
{
    use ExecuteCliCommandTrait;

    public function __construct(array $data = null)
    {
        $this->validateAndPrepareCommandData($data);

        parent::__construct($data);
    }
}
