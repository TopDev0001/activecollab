<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\MailRouter;

use ActiveCollab\ActiveCollabJobs\Utils\JobDataResolver\JobDataResolverInterface;
use PHPMailer\PHPMailer\PHPMailer;

interface MailRouterInterface
{
    public function createFromJobData(JobDataResolverInterface $job_data_resolver): PHPMailer;
    public function createNativeMailer(): PHPMailer;
    public function createSmtpMailer(
        ?string $route,
        JobDataResolverInterface $job_data_resolver,
        bool $connect = true
    ): PHPMailer;
}
