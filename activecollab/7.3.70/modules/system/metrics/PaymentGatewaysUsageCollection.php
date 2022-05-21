<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use PaymentGateway;
use Payments;
use PaypalExpressCheckoutGateway;

class PaymentGatewaysUsageCollection extends Collection
{
    public function getValueFor(DateValue $date): ResultInterface
    {
        $paypal_express = Payments::getPayPalGateway();
        $credit_card_gateway = Payments::getCreditCardGateway();

        $credit_card_gateway_name = 'disabled';
        if ($credit_card_gateway instanceof PaymentGateway && $credit_card_gateway->getIsEnabled()) {
            $credit_card_gateway_name = strtolower(str_replace('Gateway', '', $credit_card_gateway->getType()));
        }

        return $this->produceResult(
            [
                'paypal_express' => $paypal_express instanceof PaypalExpressCheckoutGateway && $paypal_express->getIsEnabled(),
                'card_processing' => $credit_card_gateway_name,
            ],
            $date
        );
    }
}
