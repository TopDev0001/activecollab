<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class ActivitiesController extends AuthRequiredController
{
    public function index(Request $request, User $user)
    {
        return Users::prepareCollection(
            sprintf(
                'cursor_range_activity_logs_for_%s_%s:%s',
                $user->getId(),
                $this->resolveDateParam($request, 'from')->toMySQL(),
                $this->resolveDateParam($request, 'to')->toMySQL()
            ),
            $user
        );
    }

    public function user(Request $request, User $user)
    {
        $by = Users::findById($request->getId('user_id'));

        if (!$by || !$by->canView($user)) {
            return Response::NOT_FOUND;
        }

        return Users::prepareCollection(
            sprintf(
                'cursor_range_activity_logs_by_%s_%s:%s',
                $by->getId(),
                $this->resolveDateParam($request, 'from')->toMySQL(),
                $this->resolveDateParam($request, 'to')->toMySQL()
            ),
            $user
        );
    }

    public function project(Request $request, User $user)
    {
        /** @var Project $project */
        $project = DataObjectPool::get(Project::class, $request->getId('project_id'));

        if (!$project->canView($user)) {
            return Response::FORBIDDEN;
        }

        AccessLogs::logAccess($project, $user);

        return Projects::prepareCollection(
            sprintf(
                'cursor_range_activity_logs_in_project_%s_%s:%s',
                $project->getId(),
                $this->resolveDateParam($request, 'from')->toMySQL(),
                $this->resolveDateParam($request, 'to')->toMySQL()
            ),
            $user
        );
    }

    private function resolveDateParam(Request $request, string $param_name): DateValue
    {
        return DateValue::makeFromString((string) $request->get($param_name)) ?? DateValue::now();
    }
}
