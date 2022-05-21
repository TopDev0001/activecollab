<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class StripeIntegration extends CreditCardIntegration
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
        return 'Stripe';
    }

    public function getShortName(): string
    {
        return 'stripe';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return lang('Web and mobile payments');
    }

    public function isInUse(User $user = null): bool
    {
        if ($gateway = Payments::getCreditCardGateway()) {
            return $gateway instanceof StripeGateway && $gateway->getIsEnabled();
        }

        return false;
    }
}
