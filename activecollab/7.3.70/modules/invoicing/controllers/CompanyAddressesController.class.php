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
 * Company addresses controller.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage controllers
 */
class CompanyAddressesController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->isFinancialManager()) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * @return array
     */
    public function index()
    {
        return Invoices::getCompanyAddresses();
    }
}
