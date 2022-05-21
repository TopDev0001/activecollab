<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger;

use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookDisabledAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookPriorityChangeAutomationInterface;

interface WebhookAutomationLoggerInterface
{
    public function getLatestAutomation(
        int $instance_id,
        int $webhook_id
    ): ?WebhookAutomationInterface;

    public function recordPriorityChange(
        int $instance_id,
        int $webhook_id,
        WebhookPriorityChangeAutomationInterface $priority_change_automation
    ): void;

    public function recordDisabled(
        int $instance_id,
        int $webhook_id,
        WebhookDisabledAutomationInterface $disabled_automation
    ): void;

    public function jobPrioritiesToPriorityChange(
        int $from_priority,
        int $to_priority
    ): ?string;
}
