<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation;

use ActiveCollab\DateValue\DateTimeValueInterface;

interface WebhookAutomationInterface
{
    public function getExecutedAt(): DateTimeValueInterface;
}
