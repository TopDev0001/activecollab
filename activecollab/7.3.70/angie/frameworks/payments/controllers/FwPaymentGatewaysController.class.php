<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level payment gateway administration controller.
 *
 * @package angie.frameworks.payments
 * @subpackage controllers
 */
abstract class FwPaymentGatewaysController extends AuthRequiredController
{
    /**
     * Return payment gateways.
     */
    public function get_settings()
    {
        return ['paypal' => Payments::getPayPalGateway(), 'credit_card' => Payments::getPaymentCreditCardGateway()];
    }

    /**
     * Update payment gateway settings.
     *
     * @return int|array
     */
    public function update_settings(Request $request, User $user)
    {
        if (!$user->isOwner()) {
            return Response::FORBIDDEN;
        }

        $paypal_settings = $request->put('paypal');
        $credit_card_settings = $request->put('credit_card');

        if ($paypal_settings && is_foreachable($paypal_settings)) {
            if (isset($paypal_settings['is_enabled']) && $paypal_settings['is_enabled']) {
                Payments::updatePaypalGateway($this->getGatewayFromSettings($paypal_settings));
            } else {
                $paypal_gateway = Payments::getPayPalGateway();
                if ($paypal_gateway instanceof PaymentGateway) {
                    $paypal_gateway->setIsEnabled(false);
                    $paypal_gateway->save();
                }
            }
        }

        if ($credit_card_settings && is_foreachable($credit_card_settings)) {
            if (isset($credit_card_settings['is_enabled']) && $credit_card_settings['is_enabled']) {
                Payments::updateCreditCardGateway($this->getGatewayFromSettings($credit_card_settings));
            } else {
                $credit_card_gateway = Payments::getCreditCardGateway();
                if ($credit_card_gateway instanceof PaymentGateway) {
                    $credit_card_gateway->setIsEnabled(false);
                    if ($credit_card_gateway->save()) {
                        Payments::setCreditCardGateway($credit_card_gateway);
                    }
                }
            }
        }

        Integrations::clearCache();

        return ['paypal' => Payments::getPayPalGateway(), 'credit_card' => Payments::getPaymentCreditCardGateway()];
    }

    /**
     * Return payment gateway instance from settings.
     *
     * @return PaymentGateway|PaypalExpressCheckoutGateway
     * @throws InvalidParamError
     */
    private function getGatewayFromSettings(array $settings)
    {
        $gateway_class = isset($settings['type']) && $settings['type'] && (new ReflectionClass($settings['type']))->isSubclassOf('PaymentGateway') ? array_var($settings, 'type', null, true) : null;

        if ($gateway_class) {
            /** @var PaymentGateway $gateway */
            $gateway = new $gateway_class();
            $gateway->setCredentials($settings);
            $gateway->setIsEnabled(true);

            return $gateway;
        }

        throw new InvalidParamError('settings', $settings, 'Gateway type is required');
    }

    /**
     * Clear PayPal gateway settings.
     */
    public function clear_paypal()
    {
        $paypal_gateway = Payments::getPayPalGateway();

        if ($paypal_gateway instanceof PaymentGateway) {
            $paypal_gateway->delete();
        }

        return Response::OK;
    }

    /**
     * Clear credit card gateway settings.
     */
    public function clear_credit_card()
    {
        $gateway = Payments::getCreditCardGateway();

        if ($gateway instanceof PaymentGateway) {
            $gateway->delete();
        }

        return Response::OK;
    }
}
