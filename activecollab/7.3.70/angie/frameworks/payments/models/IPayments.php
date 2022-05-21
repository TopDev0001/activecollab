<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Payments interface.
 *
 * @package angie.frameworks.payments
 * @subpackage models
 */
interface IPayments
{
    /**
     * Return payments that are recorded for this object.
     *
     * @return Payment[]
     */
    public function getPayments();

    /**
     * Return balance due.
     *
     * @return float
     */
    public function getBalanceDue();

    /**
     * Return ID of the object that supports payments.
     *
     * @return int
     */
    public function getId();

    /**
     * Return currency.
     *
     * @return Currency
     */
    public function getCurrency();

    /**
     * Return description.
     *
     * @return mixed
     */
    public function getDescription();

    // ---------------------------------------------------
    //  Payment processing
    // ---------------------------------------------------

    /**
     * Return true if payment can be made on this object.
     *
     * @return bool
     */
    public function canMakePayment();

    /**
     * Return true if card can be stored for this object.
     *
     * @return bool
     */
    public function canStoreCard();

    /**
     * Make a single payment using credit card data that is provided.
     *
     * @param  float   $amount
     * @param  string  $token
     * @param  string  $email
     * @return Payment
     */
    public function payWithCreditCard($amount, $token, $email = null);

    /**
     * Make a payment after paypal redirect.
     *
     * @param string $amount
     * @param array  $params
     */
    public function payAfterPayPalPayment($amount, $params);

    /**
     * Init PayPal payment.
     *
     * @param  float $amount
     * @return array
     */
    public function initWithPayPal($amount);

    /**
     * Complete payment with PayPal.
     *
     * @param  string  $payer_id
     * @return Payment
     */
    public function completeWithPayPal(Payment $payment, $payer_id);

    /**
     * Cancel payment with paypal.
     *
     * @return Payment
     * @throws InvalidInstanceError
     */
    public function cancelPayPalPayment(Payment $payment);

    // ---------------------------------------------------
    //  Events
    // ---------------------------------------------------

    /**
     * Record new payment.
     */
    public function recordNewPayment(Payment $payment);

    /**
     * Record whne payment made to this object is updated.
     */
    public function recordPaymentUpdate(Payment $payment);

    /**
     * Record when payment made to this object is removed.
     */
    public function recordPaymentRemoval();

    public function logSuccessfulPayment($amount, string $gateway): void;
}
