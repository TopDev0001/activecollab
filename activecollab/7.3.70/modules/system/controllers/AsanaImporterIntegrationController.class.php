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

class AsanaImporterIntegrationController extends IntegrationSingletonsController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$this->active_integration instanceof AsanaImporterIntegration) {
            return Response::CONFLICT;
        }

        return null;
    }

    public function authorize(Request $request)
    {
        $this->active_integration = Integrations::findFirstByType(AsanaImporterIntegration::class)->authorize($request->put());

        return $this->active_integration->validateCredentials();
    }

    /**
     * @return int
     */
    public function schedule_import(Request $request)
    {
        $selected_workspaces = $request->post('selected_workspaces');

        if (empty($selected_workspaces)) {
            throw new Exception('Must select at least one workspace to import.');
        } else {
            $this->active_integration->setSelectedWorkspaces($selected_workspaces);

            return $this->active_integration->scheduleImport();
        }
    }

    public function start_over()
    {
        return $this->active_integration->startOver();
    }

    public function check_status()
    {
        return $this->active_integration->checkStatus();
    }

    public function invite_users()
    {
        return $this->active_integration->invite();
    }
}
