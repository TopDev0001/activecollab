<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation;

use ActiveCollab\DateValue\DateTimeValueInterface;

abstract class WebhookAutomation implements WebhookAutomationInterface
{
    private DateTimeValueInterface $performed_at;

    public function __construct(DateTimeValueInterface $performed_at)
    {
        $this->performed_at = $performed_at;
    }

    public function getExecutedAt(): DateTimeValueInterface
    {
        return $this->performed_at;
    }
}
