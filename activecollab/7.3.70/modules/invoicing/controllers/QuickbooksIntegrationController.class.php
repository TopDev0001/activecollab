<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', SystemModule::NAME);

class QuickbooksIntegrationController extends AuthRequiredController
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
     * Return quickbooks data like clients, tax_rate etc...
     *
     * @return array
     */
    public function get_data(Request $request)
    {
        return Integrations::findFirstByType(QuickbooksIntegration::class)->fetch($request->get('entity'), $request->get('ids', []), false);
    }

    public function get_suggested_transaction_number()
    {
        return ['suggestion' => Integrations::findFirstByType(QuickbooksIntegration::class)->getNextInvoiceDocNumber()];
    }

    /**
     * Get request url.
     *
     * @return bool
     */
    public function get_request_url()
    {
        return ['request_url' => Integrations::findFirstByType('QuickbooksIntegration')->getRequestUrl()];
    }

    /**
     * Authorize.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return Integrations::findFirstByType('QuickbooksIntegration')->authorize($request->put());
    }
}
