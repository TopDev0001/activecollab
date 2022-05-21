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

class SampleProjectsIntegrationController extends IntegrationSingletonsController
{
    /**
     * @var SampleProjectsIntegration
     */
    private $sample_projects_integration;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->sample_projects_integration = $this->active_integration;

        if (!($this->sample_projects_integration instanceof SampleProjectsIntegration)) {
            return Response::CONFLICT;
        }
    }

    /**
     * Get all sample projects.
     *
     * @return array
     */
    public function index()
    {
        return $this->sample_projects_integration->getSampleProjects();
    }

    /**
     * Import sample projects.
     *
     * @return array|int
     */
    public function import(Request $request, User $user)
    {
        $project_keys = $request->post('project_keys');

        if (!is_array($project_keys) || empty($project_keys)) {
            return Response::BAD_REQUEST;
        }

        $projects = [];
        $sample_projects = $this->sample_projects_integration->getSampleProjects();

        foreach ($project_keys as $project_key) {
            if (array_key_exists($project_key, $sample_projects)) {
                $projects[] = $this->sample_projects_integration->import($project_key, $user);
            }
        }

        return $projects;
    }
}
