<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Base credit card payment integration.
 *
 * @package angie.frameworks.payments
 * @subpackage models
 */
abstract class CreditCardIntegration extends Integration implements ICardProcessingPaymentGateway
{
    /**
     * Get group of this integration.
     *
     * @return string
     */
    public function getGroup()
    {
        return 'payment_processing';
    }

    /**
     * Process credit card and return payment instance.
     *
     * @param  float                  $amount
     * @param  string                 $token
     * @param  string|null            $comment
     * @return PaymentGatewayResponse
     * @throws PaymentGatewayError
     */
    public function processCreditCard($amount, Currency $currency, $token, $comment = null)
    {
        // TODO: Implement processCreditCard() method.
    }

    public function getToken($invoice)
    {
    }
}
