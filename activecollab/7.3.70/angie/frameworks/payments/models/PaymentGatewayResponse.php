<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;

/**
 * Payment gateway response.
 *
 * @package angie.frameworks.payments
 * @subpackage models
 */
class PaymentGatewayResponse
{
    /**
     * @var float
     */
    private $amount;

    /**
     * @var int|string
     */
    private $transaction_id;

    /**
     * @var DateTimeValue
     */
    private $paid_on;

    /**
     * @var string|null
     */
    private $token;

    /**
     * @param float              $amount
     * @param int|string         $transaction_id
     * @param DateTimeValue|null $paid_on
     * @param string|null        $token
     */
    public function __construct(
        $amount,
        $transaction_id,
        $paid_on = null,
        $token = null
    ) {
        $this->amount = $amount;
        $this->transaction_id = $transaction_id;
        $this->paid_on = $paid_on instanceof DateTimeValue ? $paid_on : DateTimeValue::now()->advance(Globalization::getGmtOffset(), false);
        $this->token = $token;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return int|string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @return DateTimeValue
     */
    public function getPaidOn()
    {
        return $this->paid_on;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}
