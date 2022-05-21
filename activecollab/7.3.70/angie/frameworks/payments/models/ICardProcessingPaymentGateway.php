<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Card processing gateway.
 *
 * @package angie.frameworks.payments
 * @subpackage model
 */
interface ICardProcessingPaymentGateway
{
    /**
     * Process credit card and return payment instance.
     *
     * @param  float                  $amount
     * @param  string                 $token
     * @param  string|null            $comment
     * @return PaymentGatewayResponse
     */
    public function processCreditCard($amount, Currency $currency, $token, $comment = null);

    /**
     * Request secure token.
     *
     * @param  Invoice $invoice
     * @return mixed
     */
    public function getToken($invoice);
}
