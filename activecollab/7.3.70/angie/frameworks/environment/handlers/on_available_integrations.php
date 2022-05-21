<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * on_available_integrations event handler.
 *
 * @package angie.frameworks.environment
 * @subpackage handlers
 */

use ActiveCollab\Module\System\Features\WebhooksIntegrationFeatureInterface;
use Angie\Utils\FeatureStatusResolver\FeatureStatusResolverInterface;

/**
 * Handle on_available_integrations event.
 */
function environment_handle_on_available_integrations(array &$integrations, User &$user)
{
    $feature_status_resolver = AngieApplication::getContainer()->get(FeatureStatusResolverInterface::class);
    $feature = AngieApplication::featureFactory()->makeFeature(WebhooksIntegrationFeatureInterface::NAME);
    $webhook_integration = $feature_status_resolver->isEnabled($feature);

    if ($user instanceof Owner && $webhook_integration) {
        $integrations[] = new WebhooksIntegration();
    }
}
