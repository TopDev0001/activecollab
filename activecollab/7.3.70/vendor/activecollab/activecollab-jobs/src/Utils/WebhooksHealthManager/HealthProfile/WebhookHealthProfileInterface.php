<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\HealthProfile;

interface WebhookHealthProfileInterface
{
    public function countAll(): int;
    public function countAllFailures(): int;
    public function getFailureRate(): int;
    public function countSuccesses(): int;
    public function countFailures(): int;
    public function countExceptions(): int;
}
