<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Modules\AngieFramework;

const CUSTOM_PAYMENT = 'Custom Payment';
const PAYPAL_DIRECT_PAYMENT = 'Paypal Direct Gateway';
const PAYPAL_EXPRESS_CHECKOUT = 'Paypal Express Checkout Gateway';
const AUTHORIZE_AIM = 'Authorize AIM Gateway';
const STRIPE_PAYMENT = 'Stripe Gateway';
const BRAINTREE_PAYMENT = 'Stripe Gateway';

class PaymentsFramework extends AngieFramework
{
    const NAME = 'payments';
    const PATH = __DIR__;

    protected string $name = 'payments';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            PaymentGateway::class,
            function (array $ids): ?iterable
            {
                return PaymentGateways::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            Payment::class,
            function (array $ids): ?iterable
            {
                return Payments::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                IPayments::class => __DIR__ . '/models/IPayments.php',
                IPaymentsImplementation::class => __DIR__ . '/models/IPaymentsImplementation.php',

                PaymentGatewayResponse::class => __DIR__ . '/models/PaymentGatewayResponse.php',
                PaymentGatewayError::class => __DIR__ . '/models/PaymentGatewayError.php',
                StripePaymentGatewayResponse::class => __DIR__ . '/models/StripePaymentGatewayResponse.php',

                FwPayment::class => __DIR__ . '/models/payments/FwPayment.php',
                FwPayments::class => __DIR__ . '/models/payments/FwPayments.php',

                FwPaymentGateway::class => __DIR__ . '/models/payment_gateways/FwPaymentGateway.php',
                FwPaymentGateways::class => __DIR__ . '/models/payment_gateways/FwPaymentGateways.php',

                FwStoredCard::class => __DIR__ . '/models/stored_cards/FwStoredCard.php',
                FwStoredCards::class => __DIR__ . '/models/stored_cards/FwStoredCards.php',

                FwPaymentReceivedNotification::class => __DIR__ . '/notifications/FwPaymentReceivedNotification.class.php',

                // ---------------------------------------------------
                //  Services
                // ---------------------------------------------------

                ICardProcessingPaymentGateway::class => __DIR__ . '/models/ICardProcessingPaymentGateway.php',
                ICardProcessingPaymentGatewayImplementation::class => __DIR__ . '/models/ICardProcessingPaymentGatewayImplementation.php',

                AuthorizeGateway::class => __DIR__ . '/models/services/AuthorizeGateway.php',
                StripeGateway::class => __DIR__ . '/models/services/StripeGateway.php',
                BrainTreeGateway::class => __DIR__ . '/models/services/BrainTreeGateway.php',
                PaypalGateway::class => __DIR__ . '/models/services/paypal/PaypalGateway.php',
                PaypalDirectGateway::class => __DIR__ . '/models/services/paypal/PaypalDirectGateway.php',
                PaypalExpressCheckoutGateway::class => __DIR__ . '/models/services/paypal/PaypalExpressCheckoutGateway.php',

                // ---------------------------------------------------
                //  Integrations
                // ---------------------------------------------------

                CreditCardIntegration::class => __DIR__ . '/models/integrations/CreditCardIntegration.php',
                AuthorizenetIntegration::class => __DIR__ . '/models/integrations/AuthorizenetIntegration.php',
                StripeIntegration::class => __DIR__ . '/models/integrations/StripeIntegration.php',
                PaypalDirectIntegration::class => __DIR__ . '/models/integrations/PaypalDirectIntegration.php',
                BraintreeIntegration::class => __DIR__ . '/models/integrations/BraintreeIntegration.php',

                PaypalExpressIntegration::class => __DIR__ . '/models/integrations/PaypalExpressIntegration.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_available_integrations');
    }
}
