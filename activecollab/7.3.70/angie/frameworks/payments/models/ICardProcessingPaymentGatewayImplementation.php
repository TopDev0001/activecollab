<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Card processing payment gateway implementation.
 *
 * @package angie.frameworks.payments
 * @subpackage model
 */
trait ICardProcessingPaymentGatewayImplementation
{
    /**
     * Return true if this gateway can process credit cards.
     *
     * @return bool
     */
    public function canProcessCreditCards()
    {
        return true;
    }

    /**
     * Prepare amount.
     *
     * Note: Extracted into a separate method so it can be overriden by specific gateways (Stripe for example requires
     * amount to be in cents, @see StripeGateway::prepareAmount()).
     *
     * @param  float $amount
     * @return float
     */
    public function prepareAmount($amount, Currency $currency)
    {
        return round_up($amount, $currency->getDecimalSpaces());
    }

    /**
     * Prepare (cast, trip, clean-up) and validate payment data before sending it over to the gateway.
     *
     * @param  float            $amount
     * @throws ValidationErrors
     */
    protected function prepareAndValidatePaymentData(&$amount, Currency $currency)
    {
        $errors = new ValidationErrors();

        $amount = $this->prepareAmount($amount, $currency);
        if ($amount <= 0) {
            $errors->addError('Amount is required', 'amount');
        }

        if ($errors->hasErrors()) {
            throw new $errors();
        }
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    /**
     * Return true if the parent gateway is in production mode.
     *
     * @return bool
     */
    abstract public function isLive();
}
