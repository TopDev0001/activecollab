<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Error;

/**
 * Payment gateway error.
 *
 * @package angie.frameworks.payments
 * @subpackage models
 */
class PaymentGatewayError extends Error
{
    /**
     * Construct error object.
     *
     * @param string|null    $message
     * @param array|null     $additional
     * @param Exception|null $previous
     */
    public function __construct($message = '', $additional = null, $previous = null)
    {
        if (empty($message)) {
            $message = 'Payment failed';
        }

        parent::__construct($message, $additional, $previous);
    }
}
