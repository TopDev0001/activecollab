<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager;

use ActiveCollab\ActiveCollabJobs\Utils\WebhooksDispatcher\Result\WebhookDispatchResultInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhooksHealthManager\HealthProfile\WebhookHealthProfileInterface;

interface WebhooksHealthManagerInterface
{
    public function notify(
        int $instance_id,
        int $webhook_id,
        int $job_priority,
        WebhookDispatchResultInterface $result
    ): void;

    public function shouldDisable(
        int $instance_id,
        int $webhook_id,
        WebhookHealthProfileInterface $health_profile
    ): bool;
}
