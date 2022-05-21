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

class XeroIntegrationController extends AuthRequiredController
{
    public function __before(Request $request, $user)
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
     * Return xero data like clients, tax_rate etc...
     *
     * @return array
     */
    public function get_data()
    {
        $xero = Integrations::findFirstByType('XeroIntegration');

        return [
            'clients' => $xero->getCompanies(),
            'accounts' => $xero->getAccounts(),
            'item_codes' => $xero->getItemCodes(),
        ];
    }

    public function get_authorization_url()
    {
        return ['authorization_url' => Integrations::findFirstByType('XeroIntegration')->getAuthorizationUrl()];
    }

    /**
     * Authorize.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return Integrations::findFirstByType('XeroIntegration')->authorize($request->put());
    }
}
