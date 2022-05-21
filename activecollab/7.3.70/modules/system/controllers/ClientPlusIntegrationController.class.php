<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('integration_singletons', SystemModule::NAME);

class ClientPlusIntegrationController extends IntegrationSingletonsController
{
    /**
     * @var ClientPlusIntegration
     */
    protected $integration;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->integration = Integrations::findFirstByType('ClientPlusIntegration', false);

        if (!($this->integration instanceof ClientPlusIntegration)) {
            return Response::CONFLICT;
        }
    }

    /**
     * Activate integration.
     *
     * @return $this|int
     */
    public function activate(Request $request, User $user)
    {
        return $this->integration->canEdit($user) ? $this->integration->enable() : Response::FORBIDDEN;
    }

    /**
     * Deactivate integration.
     *
     * @return bool|int
     */
    public function deactivate(Request $request, User $user)
    {
        return $this->integration->canDelete($user) ? $this->integration->delete($user) : Response::FORBIDDEN;
    }
}
