<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StaticHtmlFile\StaticHtmlFile;

AngieApplication::useController('auth_not_required', EnvironmentFramework::INJECT_INTO);

/**
 * Freamework public payments controller.
 *
 * @package angie.freameworks.payments
 * @subpackage controllers
 */
abstract class FwPublicPaymentsController extends AuthNotRequiredController
{
    /**
     * Return payments.
     *
     * @return array|int
     */
    public function view(Request $request)
    {
        $number = $request->get('number');
        $hash = $request->get('hash');

        if ($number && $hash) {
            $payment = Payments::findOne(
                [
                    'conditions' => ['id = ?', $number],
                ]
            );

            if ($payment instanceof Payment && $payment->getHash() === $hash) {
                return [
                    'payment' => $payment,
                    'currency' => $payment->getCurrency(),
                ];
            }

            return Response::FORBIDDEN;
        }

        return null;
    }

    /**
     * Add new public payment.
     *
     * @return array
     * @throws Error
     */
    public function add(Request $request)
    {
        $post = $request->post();

        if (empty($post)) {
            $post = [];
        }

        $parent = DataObjectPool::get($post['parent_type'], $post['parent_id']);
        if (!$parent instanceof IPayments) {
            throw new InvalidInstanceError($post['parent_type'], $parent, IPayments::class);
        }

        $token = !empty($post['token']) ? $post['token'] : null;

        $method = array_var($post, 'method');
        $amount = is_null($token) ? array_var($post, 'amount') : $parent->getBalanceDue();

        if ($method == Payment::CREDIT_CARD) {
            if (is_null($token)) {
                $params = [
                    'paid_on' => array_var($post, 'paid_on'),
                    'transaction_id' => array_var($post, 'transaction_id'),
                    'email' => array_var($post, 'email'),
                    'name' => array_var($post, 'name'),
                ];

                return $parent->payAfterPayPalPayment($amount, $params);
            } else {
                return $parent->payWithCreditCard($amount, $token);
            }
        } elseif ($method == Payment::PAYPAL) {
            return [
                'redirect_url' => $parent->initWithPayPal($parent->getBalanceDue()),
            ];
        } else {
            throw new Error('Invalid Payment method.');
        }
    }

    /**
     * Update payment - used for returning from paypal service - just for Paypal payments.
     *
     * if payer_id is present then complete payment, else cancel it
     *
     * @return array|int
     * @throws InvalidInstanceError
     */
    public function update(Request $request)
    {
        $put = $request->put();

        /** @var Payment $payment */
        $payment = Payments::findByToken($put['token']);

        if ($payment instanceof Payment && $payment->getMethod() == Payment::PAYPAL) {
            $parent = $payment->getParent();
            if (!$parent instanceof IPayments) {
                throw new InvalidInstanceError('', $parent, 'IPayments');
            }
            $payer_id = $put['payer_id'];

            if ($payer_id) {
                $payment = $parent->completeWithPayPal($payment, $put['payer_id']);
            } else {
                $payment = $parent->cancelPayPalPayment($payment);
            }

            return [
                'payment' => $payment,
                'currency' => $payment->getCurrency(),
            ];
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Returns js for authorize net iframe.
     *
     * @return StaticHtmlFile
     */
    public function authorizenet_confirm()
    {
        return new StaticHtmlFile(ANGIE_PATH . '/frameworks/payments/resources/html/authorize_net_communication_iframe.html');
    }

    /**
     * Returns js for authorize net iframe.
     *
     * @return StaticHtmlFile|int
     */
    public function authorizenet_form(Request $request)
    {
        $token = $request->get('token', null);
        $payment_url = $request->get('payment_url', null);

        if (is_null($token) || is_null($payment_url)) {
            return \Angie\Http\Response::INVALID_PROPERTIES;
        }

        return new StaticHtmlFile(ANGIE_PATH . '/frameworks/payments/resources/html/authorize_net_main_form.html', ['--URL--' => $payment_url, '--TOKEN--' => $token, '--SUBMIT--' => lang('Continue to Authorize.Net')]);
    }
}
