<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class AuthorizenetIntegration extends CreditCardIntegration
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
        return 'AuthorizeNet';
    }

    public function getShortName(): string
    {
        return 'authorize-net';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return lang('Credit card processing service');
    }

    public function isInUse(User $user = null): bool
    {
        if ($gateway = Payments::getCreditCardGateway()) {
            return $gateway instanceof AuthorizeGateway && $gateway->getIsEnabled();
        }

        return false;
    }
}
