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
 * Unscheduled tasks controller.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage controllers
 */
class UnscheduledTasksController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->canUseReports()) {
            return Response::FORBIDDEN;
        }

        return null;
    }

    /**
     * Return number of unscheduled tasks by projects.
     *
     * @return array|int
     */
    public function count_by_project(Request $request)
    {
        $project_ids = null;

        if ($project_ids = trim($request->get('project_ids'))) {
            $project_ids = array_map(function ($id) {
                return (int) $id;
            }, explode(',', $project_ids));

            $project_ids = array_filter($project_ids); // Remove empty values
        }

        if (empty($project_ids)) {
            return Response::BAD_REQUEST;
        }

        return Tasks::countUnscheduledInProjects($project_ids);
    }
}
