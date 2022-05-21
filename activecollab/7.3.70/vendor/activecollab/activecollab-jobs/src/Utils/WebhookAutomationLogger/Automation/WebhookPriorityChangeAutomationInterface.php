<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJobs\Utils\WebhookAutomationLogger\Automation;

interface WebhookPriorityChangeAutomationInterface extends WebhookAutomationInterface
{
    const HIGH_TO_NORMAL = 'high_to_normal';
    const NORMAL_TO_LOW = 'normal_to_low';
    const LOW_TO_NORMAL = 'low_to_normal';
    const NORMAL_TO_HIGH = 'normal_to_high';

    const SUPPORTED_CHANGES = [
        self::HIGH_TO_NORMAL,
        self::NORMAL_TO_LOW,
        self::LOW_TO_NORMAL,
        self::NORMAL_TO_HIGH,
    ];

    public function getPriorityChange(): string;
}
