<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * on_available_integrations event handler.
 *
 * @package ActiveCollab.modules.system
 * @subpackage handlers
 */

use ActiveCollab\Foundation\App\Channel\OnDemandChannelInterface;
use ActiveCollab\Module\System\Features\SlackIntegrationFeatureInterface;
use ActiveCollab\Module\System\Features\WebhooksIntegrationFeatureInterface;
use Angie\Utils\FeatureStatusResolver\FeatureStatusResolverInterface;

/**
 * Handle on_available_integrations event.
 */
function system_handle_on_available_integrations(array &$integrations, User &$user)
{
    if ($user instanceof Owner) {
        $feature_status_resolver = AngieApplication::getContainer()->get(FeatureStatusResolverInterface::class);

        $webhook_feature = AngieApplication::featureFactory()->makeFeature(WebhooksIntegrationFeatureInterface::NAME);
        $is_webhooks_integration_available = $feature_status_resolver->isEnabled($webhook_feature);

        $slack_feature = AngieApplication::featureFactory()->makeFeature(SlackIntegrationFeatureInterface::NAME);
        $slack_integration = $feature_status_resolver->isEnabled($slack_feature);

        $integrations[] = Integrations::findFirstByType(ClientPlusIntegration::class);
        if ($slack_integration) {
            $integrations[] = Integrations::findFirstByType(SlackIntegration::class);
        }
        $integrations[] = Integrations::findFirstByType(BasecampImporterIntegration::class);
        $integrations[] = Integrations::findFirstByType(TrelloImporterIntegration::class);
        $integrations[] = Integrations::findFirstByType(TestLodgeIntegration::class);
        $integrations[] = Integrations::findFirstByType(HubstaffIntegration::class);
        $integrations[] = Integrations::findFirstByType(TimeCampIntegration::class);
        if ($is_webhooks_integration_available) {
            $integrations[] = Integrations::findFirstByType(ZapierIntegration::class);
        }
        $integrations[] = Integrations::findFirstByType(WrikeImporterIntegration::class);
        $integrations[] = Integrations::findFirstByType(AsanaImporterIntegration::class);

        if (!AngieApplication::isOnDemand()) {
            $integrations[] = Integrations::findFirstByType(DropboxIntegration::class);
            $integrations[] = Integrations::findFirstByType(GoogleDriveIntegration::class);
        }

        if (AngieApplication::getContainer()->get(OnDemandChannelInterface::class)->isEdgeChannel()) {
            $integrations[] = Integrations::findFirstByType(OneLoginIntegration::class);
        }
    }

    if ($user->isPowerUser()) {
        $integrations[] = Integrations::findFirstByType(SampleProjectsIntegration::class);
    }

    $integrations[] = Integrations::findFirstByType(DesktopAppIntegration::class);
}
