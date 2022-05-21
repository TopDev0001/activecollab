<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\JobDataResolver;

class JobDataResolver implements JobDataResolverInterface
{
    private array $job_data;

    public function __construct(array $job_data)
    {
        $this->job_data = $job_data;
    }

    public function getArgumentValue(string $data_argument)
    {
        return $this->job_data[$data_argument] ?? null;
    }
}
