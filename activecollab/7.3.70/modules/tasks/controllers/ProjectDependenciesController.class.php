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

class ProjectDependenciesController extends AuthRequiredController
{
    /**
     * @var Project
     */
    protected $active_project;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        $this->active_project = DataObjectPool::get(Project::class, $request->getId('project_id'));

        if (empty($this->active_project)) {
            return Response::NOT_FOUND;
        }

        if (!$this->active_project->canView($user)) {
            return Response::FORBIDDEN;
        }

        return null;
    }

    public function view(Request $request, $user)
    {
        return AngieApplication::taskDependenciesResolver($user)
            ->getProjectDependenciesCollection($this->active_project->getId());
    }
}
