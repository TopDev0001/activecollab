<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Paypal express checkout payment class.
 *
 * @package angie.framework.payments
 * @subpackage models
 */
class PaypalExpressCheckoutGateway extends PaypalGateway
{
    /**
     * Return array of supported currencies, or true if all currencies are supported. Currently 24 supported by PaypalExpress.
     *
     * @return bool|array
     */
    protected function getSupportedCurrencies()
    {
        return [
            'AUD',
            'BRL',
            'CAD',
            'CZK',
            'DKK',
            'EUR',
            'HKD',
            'HUF',
            'ILS',
            'JPY',
            'MYR',
            'MXN',
            'NOK',
            'NZD',
            'PHP',
            'PLN',
            'GBP',
            'RUB',
            'SGD',
            'SEK',
            'CHF',
            'TWD',
            'THB',
            'USD',
        ];
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
        return round_up($amount);
    }

    /**
     * Make initial request and retrive token from PayPal.
     *
     * @param  float                  $amount
     * @param  string                 $description
     * @return PaymentGatewayResponse
     * @throws PaymentGatewayError
     */
    public function makeInitialRequest($amount, Currency $currency, $description)
    {
        $response = $this->callService(PaypalGateway::PAYPAL_SET_EXPRESS_CHECKOUT_METHOD, $this->prepareNvpString([
            'LANDINGPAGE' => 'Billing',
            'SOLUTIONTYPE' => 'Sole',
            'RETURNURL' => $this->getReturnUrl(),
            'CANCELURL' => $this->getCancelUrl(),
            'PAYMENTACTION' => 'Sale',
            'AMT' => $this->prepareAmount($amount, $currency),
            'CURRENCYCODE' => $currency->getCode(),
            'L_DESC0' => $description,
            'L_AMT0' => $this->prepareAmount($amount, $currency),
            'L_QTY0' => 1,
        ]));

        if (strtoupper($response['ACK']) == 'SUCCESS' || strtoupper($response['ACK']) == 'SUCCESSWITHWARNING') {
            return new PaymentGatewayResponse($amount, $response['CORRELATIONID'], null, $response['TOKEN']);
        } else {
            $error_message = '';

            for ($i = 0; $i <= 5; ++$i) {
                if (isset($response["L_SHORTMESSAGE$i"])) {
                    $error_message .= ' ' . $response["L_SHORTMESSAGE$i"];
                }
            }
            throw new PaymentGatewayError($error_message);
        }
    }

    /**
     * Complete payment.
     *
     * @param  string                 $payer_id
     * @return PaymentGatewayResponse
     * @throws PaymentGatewayError
     */
    public function completePayment(Payment $payment, $payer_id)
    {
        $response = $this->callService(PaypalGateway::PAYPAL_DO_EXPRESS_CHECKOUT_METHOD, $this->prepareNvpString([
            'TOKEN' => $payment->getToken(),
            'PAYERID' => $payer_id,
            'PAYMENTACTION' => 'Sale',
            'AMT' => $this->prepareAmount($payment->getAmount(), $payment->getCurrency()),
            'CURRENCYCODE' => $payment->getCurrency()->getCode(),
            'BUTTONSOURCE' => 'ACTIVECOLLAB_SP',
        ]));

        if (strtoupper($response['ACK']) == 'SUCCESS' || strtoupper($response['ACK']) == 'SUCCESSWITHWARNING') {
            return new PaymentGatewayResponse($payment->getAmount(), $response['CORRELATIONID'], null, $response['TOKEN']);
        } else {
            $error_message = '';

            for ($i = 0; $i <= 5; ++$i) {
                if (isset($response["L_SHORTMESSAGE$i"])) {
                    $error_message .= ' ' . $response["L_SHORTMESSAGE$i"];
                }
            }
            throw new PaymentGatewayError($error_message);
        }
    }

    /**
     * Return url for completing payment.
     *
     * @param $payment
     * @return string
     */
    public function getCompletePaymentUrl(Payment $payment)
    {
        $redirect_url = $this->isLive() ? PaypalGateway::REDIRECT_URL : PaypalGateway::TEST_REDIRECT_URL;

        return $redirect_url . '&token=' . $payment->getToken();
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return ROOT_URL . '/s/payment-completed';
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return ROOT_URL . '/s/payment-completed';
    }
}
