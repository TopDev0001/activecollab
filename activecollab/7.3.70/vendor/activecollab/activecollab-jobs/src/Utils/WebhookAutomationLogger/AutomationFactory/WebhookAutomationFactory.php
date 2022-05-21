<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\AutomationFactory;

use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookDisabledAutomation;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookDisabledAutomationInterface;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookPriorityChangeAutomation;
use ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation\WebhookPriorityChangeAutomationInterface;
use ActiveCollab\DateValue\DateTimeValueInterface;
use InvalidArgumentException;

class WebhookAutomationFactory implements WebhookAutomationFactoryInterface
{
    public function createAutomation(
        string $automation,
        DateTimeValueInterface $executed_at
    ): WebhookAutomationInterface
    {
        if (in_array($automation,WebhookPriorityChangeAutomationInterface::SUPPORTED_CHANGES)) {
            return new WebhookPriorityChangeAutomation($automation, $executed_at);
        } elseif ($automation === WebhookDisabledAutomationInterface::DISABLED) {
            return new WebhookDisabledAutomation($executed_at);
        }

        throw new InvalidArgumentException(sprintf('Unknown webhook automation "%s".', $automation));
    }
}
