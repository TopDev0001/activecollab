<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\AutomationFactory;

use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookAutomationInterface;
use ActiveCollab\DateValue\DateTimeValueInterface;

interface WebhookAutomationFactoryInterface
{
    public function createAutomation(
        string $automation,
        DateTimeValueInterface $executed_at
    ): WebhookAutomationInterface;
}
