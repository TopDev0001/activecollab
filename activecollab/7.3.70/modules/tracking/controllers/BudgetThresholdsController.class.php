<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\Tracking\Utils\BudgetNotificationsManagerInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', SystemModule::NAME);

class BudgetThresholdsController extends AuthRequiredController
{
    /**
     * Active project.
     *
     * @var Project
     */
    protected $active_project;

    protected function __before(Request $request, $user)
    {
        if ($before_result = parent::__before($request, $user)) {
            return $before_result;
        }

        if ($project_id = $request->getId('project_id')) {
            if ($this->active_project = DataObjectPool::get('Project', $project_id)) {
                if (!$this->active_project->canSeeBudget($user)) {
                    return Response::FORBIDDEN;
                }
            } else {
                return Response::NOT_FOUND;
            }
        } else {
            $this->active_project = new Project();
        }

        return null;
    }

    public function index(Request $request, User $user)
    {
        try {
            return BudgetThresholds::prepareCollection('budget_thresholds_for_' . $request->get('project_id'), $user);
        } catch (Exception $exception) {
            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Thresholds defined for this Project cannot be shown.')]
            );
        }
    }

    public function add(Request $request, User $user)
    {
        if (!$request->post('project_id') || !is_array($request->post('attributes'))) {
            return Response::BAD_REQUEST;
        }

        return AngieApplication::getContainer()
            ->get(BudgetNotificationsManagerInterface::class)
            ->batchEditThresholds($request->post('attributes'), $request->post('project_id'));
    }
}
