<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class SlackIntegrationFeature extends Feature implements SlackIntegrationFeatureInterface
{
    public function getName(): string
    {
        return SlackIntegrationFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return SlackIntegrationFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'slack_integration_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'slack_integration_enabled_lock';
    }
}
