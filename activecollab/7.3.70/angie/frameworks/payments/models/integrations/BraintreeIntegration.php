<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class BraintreeIntegration extends CreditCardIntegration
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
        return 'Braintree';
    }

    public function getShortName(): string
    {
        return 'braintree';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return lang('Accept PayPal, Bitcoin, Apple Pay, and credit cards');
    }

    public function isInUse(User $user = null): bool
    {
        if ($gateway = Payments::getCreditCardGateway()) {
            return $gateway instanceof BrainTreeGateway && $gateway->getIsEnabled();
        }

        return false;
    }
}
