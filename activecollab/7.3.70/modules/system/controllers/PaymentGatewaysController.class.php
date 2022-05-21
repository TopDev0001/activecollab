<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('fw_payment_gateways', PaymentsFramework::NAME);

/**
 * Payment gateway administration controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class PaymentGatewaysController extends FwPaymentGatewaysController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->isFinancialManager()) {
            return Response::FORBIDDEN;
        }
    }
}
