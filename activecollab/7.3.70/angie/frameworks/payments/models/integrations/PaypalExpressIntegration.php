<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class PaypalExpressIntegration extends Integration
{
    public function isSingleton(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'PayPal Express Checkout';
    }

    public function getShortName(): string
    {
        return 'paypal-express';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return lang('Accept PayPal payments');
    }

    /**
     * Get group of this integration.
     *
     * @return string
     */
    public function getGroup()
    {
        return 'payment_processing';
    }

    public function isInUse(User $user = null): bool
    {
        if ($gateway = Payments::getPayPalGateway()) {
            return $gateway instanceof PaypalExpressCheckoutGateway && $gateway->getIsEnabled();
        }

        return false;
    }
}
