<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Features;

use Angie\Features\Feature;

class WebhooksIntegrationFeature extends Feature implements WebhooksIntegrationFeatureInterface
{
    public function getName(): string
    {
        return WebhooksIntegrationFeatureInterface::NAME;
    }

    public function getVerbose(): string
    {
        return WebhooksIntegrationFeatureInterface::VERBOSE_NAME;
    }

    public function getAddOnsAvailableOn(): array
    {
        return [];
    }

    public function getIsEnabledFlag(): string
    {
        return 'webhooks_integration_enabled';
    }

    public function getIsEnabledLockFlag(): string
    {
        return 'webhooks_integration_enabled_lock';
    }
}
