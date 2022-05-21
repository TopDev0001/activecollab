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

class TrelloImporterIntegrationController extends IntegrationSingletonsController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!($this->active_integration instanceof TrelloImporterIntegration)) {
            return Response::CONFLICT;
        }
    }

    /**
     * Get request url.
     *
     * @return array
     */
    public function get_request_url()
    {
        return ['request_url' => Integrations::findFirstByType('TrelloImporterIntegration')->getRequestUrl()];
    }

    /**
     * Authorize.
     *
     * @return TrelloImporterIntegration
     */
    public function authorize(Request $request)
    {
        $this->active_integration = Integrations::findFirstByType('TrelloImporterIntegration')->authorize($request->put());

        return $this->active_integration->validateCredentials();
    }

    /**
     * Start import.
     *
     * @return TrelloImporterIntegration
     */
    public function schedule_import(Request $request)
    {
        $users = $request->post('users');

        if (!isset($users)) {
            return Response::BAD_REQUEST;
        }

        if ($this->active_integration->checkAndPrepareTrelloUsers($users)) {
            $this->active_integration->importTrelloUsers();

            return ['start_import' => true, 'result' => $this->active_integration->scheduleImport()];
        }

        return ['start_import' => false, 'result' => $this->active_integration->getTrelloUsers()];
    }

    /**
     * Start the process over.
     *
     * @return TrelloImporterIntegration
     */
    public function start_over()
    {
        return $this->active_integration->startOver();
    }

    /**
     * Check progress of the importer.
     *
     * @return TrelloImporterIntegration
     */
    public function check_status()
    {
        return $this->active_integration->checkStatus();
    }

    /**
     * Send users invite.
     *
     * @return TrelloImporterIntegration
     */
    public function invite_users()
    {
        return $this->active_integration->invite();
    }
}
