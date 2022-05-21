<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use ActiveCollab\ActiveCollabJobs\Jobs\Job as BaseJob;
use InvalidArgumentException;

abstract class Job extends BaseJob
{
    const CLASSIC = 'classic';
    const FEATHER = 'feather';

    public function __construct(array $data = null)
    {
        if (empty($data['instance_type'])) {
            throw new InvalidArgumentException("'instance_type' property is required");
        } elseif (!in_array($data['instance_type'], [self::CLASSIC, self::FEATHER])) {
            throw new InvalidArgumentException("'instance_type' can be 'classic' or 'feather'");
        }

        if (empty($data['tasks_path'])) {
            $data['tasks_path'] = '';
        }

        parent::__construct($data);
    }

    private ?string $multi_account_path = null;

    protected function getMultiAccountPath(): string
    {
        if ($this->multi_account_path === null) {
            $this->multi_account_path = '/var/www/activecollab-multi-account';
        }

        return $this->multi_account_path;
    }

    public function getActiveCollabCliPhpPath(): string
    {
        return "{$this->getMultiAccountPath()}/tasks/activecollab-cli.php";
    }
}
