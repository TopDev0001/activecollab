<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation;

use ActiveCollab\DateValue\DateTimeValueInterface;
use LogicException;

class WebhookPriorityChangeAutomation extends WebhookAutomation implements WebhookPriorityChangeAutomationInterface
{
    private string $priority_change;

    public function __construct(
        string $priority_change,
        DateTimeValueInterface $performed_at
    )
    {
        parent::__construct($performed_at);

        if (!in_array($priority_change, self::SUPPORTED_CHANGES)) {
            throw new LogicException(
                sprintf(
                    'Change "%s" it not a supported priority change.',
                    $priority_change
                )
            );
        }

        $this->priority_change = $priority_change;
    }

    public function getPriorityChange(): string
    {
        return $this->priority_change;
    }
}
