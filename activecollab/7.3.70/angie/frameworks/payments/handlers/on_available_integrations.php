<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_available_integrations event handler.
 *
 * @package angie.frameworks.payments
 * @subpackage handlers
 */
use ActiveCollab\Module\System\Features\InvoicesFeatureInterface;
use Angie\Utils\FeatureStatusResolver\FeatureStatusResolverInterface;

/**
 * Handle on_available_integrations event.
 */
function payments_handle_on_available_integrations(array &$integrations, User &$user)
{
    if ($user instanceof Owner) {
        $feature_status_resolver = AngieApplication::getContainer()->get(FeatureStatusResolverInterface::class);
        $feature = AngieApplication::featureFactory()->makeFeature(InvoicesFeatureInterface::NAME);
        $is_invoices_enabled = $feature_status_resolver->isEnabled($feature);

        if ($is_invoices_enabled) {
            $integrations[] = Integrations::findFirstByType(AuthorizenetIntegration::class);
            $integrations[] = Integrations::findFirstByType(StripeIntegration::class);
            $integrations[] = Integrations::findFirstByType(BraintreeIntegration::class);
            $integrations[] = Integrations::findFirstByType(PaypalDirectIntegration::class);
            $integrations[] = Integrations::findFirstByType(PaypalExpressIntegration::class);
        }
    }
}
