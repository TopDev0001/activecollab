<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class PaypalDirectIntegration extends CreditCardIntegration
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
        return 'PayPal Direct Payments';
    }

    public function getShortName(): string
    {
        return 'paypal';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return lang('Receive credit card payments (requires PayFlow Pro)');
    }

    public function isInUse(User $user = null): bool
    {
        if ($gateway = Payments::getCreditCardGateway()) {
            return $gateway instanceof PaypalDirectGateway && $gateway->getIsEnabled();
        }

        return false;
    }
}
