<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Controller\Response\StatusResponse\BadRequestStatusResponse;
use Angie\Http\Request;
use Angie\Http\Response\StatusResponse\NotFoundStatusResponse;

AngieApplication::useController('fw_public_payments', PaymentsFramework::NAME);

/**
 * Public Payments  controller.
 *
 * @package activeCollab.modules.system
 * @subpackage controllers
 */
class PublicPaymentsController extends FwPublicPaymentsController
{
    public function process_stripe_confirmation_intent(Request $request)
    {
        $payment_intent = $request->post('payment_intent_id');
        if (empty($payment_intent)) {
            return new BadRequestStatusResponse('Payment intent missng');
        }

        $payment = Payments::findOne(
            [
                'conditions' => [
                    'raw_additional_properties like ?',
                    "%{$payment_intent}%",
                ],
            ]
        );

        if (!$payment instanceof Payment) {
            return new NotFoundStatusResponse();
        }

        $parent = $payment->getParent();

        if (!$parent instanceof IPayments) {
            throw new InvalidInstanceError(get_class($parent), $parent, 'IPayments');
        }

        $gateway = Payments::getCreditCardGateway($parent);

        if (!$gateway instanceof StripeGateway) {
            throw new LogicException('Can not process Stripe payment intent as Stripe Payment gateway is not in use');
        }

        return $gateway->processStripeConfirmationIntent($payment, $payment_intent);
    }
}
